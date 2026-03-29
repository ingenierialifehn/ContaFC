<?php
declare(strict_types=1);

namespace ContaFC\Core;

use InvalidArgumentException;

/**
 * MotorContable – Valida la ecuación de partida doble y persiste comprobantes.
 *
 * Reglas implementadas:
 *  1. ΣDébito === ΣCrédito (tolerancia de 0.01 por redondeo)
 *  2. Mínimo 2 líneas por comprobante
 *  3. Periodo abierto obligatorio
 *  4. Cuenta debe aceptar movimiento (acepta_movimiento = 1)
 */
final class MotorContable
{
    private const TOLERANCIA = 0.01;

    public function __construct(
        private readonly Database $database
    ) {}

    /**
     * Registra un comprobante completo dentro de una transacción.
     *
     * @param  array<string, mixed>  $cabecera
     * @param  LineaAsiento[]        $lineas
     * @return int                   ID del comprobante creado
     * @throws InvalidArgumentException Si la partida doble no cuadra
     * @throws \RuntimeException        Si el periodo está cerrado
     */
    public function registrarComprobante(array $cabecera, array $lineas): int
    {
        $this->validarLineas($lineas);

        $pdo = $this->database->getPdo();

        // Verificar periodo abierto
        $periodoId = $this->obtenerPeriodoAbierto(
            (int)$cabecera['empresa_id'],
            $cabecera['fecha']
        );

        $pdo->beginTransaction();

        try {
            // 1. Consecutivo del tipo de comprobante
            $numero = $this->siguienteConsecutivo(
                (int)$cabecera['empresa_id'],
                (int)$cabecera['tipo_comp_id']
            );

            // 2. Insertar cabecera
            $sqlCab = <<<SQL
                INSERT INTO comprobantes
                    (empresa_id, sucursal_id, tipo_comp_id, numero, fecha, periodo_id,
                     tercero_id, observaciones, estado, usuario_id, moneda, tasa_cambio)
                VALUES
                    (:empresa_id, :sucursal_id, :tipo_comp_id, :numero, :fecha, :periodo_id,
                     :tercero_id, :observaciones, 'registrado', :usuario_id, :moneda, :tasa_cambio)
            SQL;

            $stmt = $pdo->prepare($sqlCab);
            $stmt->execute([
                ':empresa_id'    => $cabecera['empresa_id'],
                ':sucursal_id'   => $cabecera['sucursal_id'] ?? null,
                ':tipo_comp_id'  => $cabecera['tipo_comp_id'],
                ':numero'        => $numero,
                ':fecha'         => $cabecera['fecha'],
                ':periodo_id'    => $periodoId,
                ':tercero_id'    => $cabecera['tercero_id'] ?? null,
                ':observaciones' => $cabecera['observaciones'] ?? null,
                ':usuario_id'    => $cabecera['usuario_id'],
                ':moneda'        => $cabecera['moneda'] ?? 'HNL',
                ':tasa_cambio'   => $cabecera['tasa_cambio'] ?? 1.0,
            ]);

            $comprobanteId = (int)$pdo->lastInsertId();

            // 3. Insertar líneas
            $sqlLin = <<<SQL
                INSERT INTO asientos
                    (comprobante_id, empresa_id, linea, cuenta_id, tercero_id,
                     ceco_id, proyecto_id, debito, credito, descripcion,
                     doc_cruce_tipo, doc_cruce_num, doc_cruce_cuota,
                     vencimiento, base_retencion)
                VALUES
                    (:comprobante_id, :empresa_id, :linea, :cuenta_id, :tercero_id,
                     :ceco_id, :proyecto_id, :debito, :credito, :descripcion,
                     :doc_cruce_tipo, :doc_cruce_num, :doc_cruce_cuota,
                     :vencimiento, :base_retencion)
            SQL;

            $stmtLin = $pdo->prepare($sqlLin);

            foreach ($lineas as $i => $linea) {
                $this->validarCuentaAceptaMovimiento($linea->cuentaId, (int)$cabecera['empresa_id']);
                $stmtLin->execute([
                    ':comprobante_id'  => $comprobanteId,
                    ':empresa_id'      => $cabecera['empresa_id'],
                    ':linea'           => $i + 1,
                    ':cuenta_id'       => $linea->cuentaId,
                    ':tercero_id'      => $linea->terceroId,
                    ':ceco_id'         => $linea->cecoId,
                    ':proyecto_id'     => $linea->proyectoId,
                    ':debito'          => round($linea->debito, 4),
                    ':credito'         => round($linea->credito, 4),
                    ':descripcion'     => $linea->descripcion,
                    ':doc_cruce_tipo'  => $linea->docCruceTipo,
                    ':doc_cruce_num'   => $linea->docCruceNum,
                    ':doc_cruce_cuota' => $linea->docCruceCuota,
                    ':vencimiento'     => $linea->vencimiento,
                    ':base_retencion'  => round($linea->baseRetencion, 4),
                ]);
            }

            // 4. Actualizar saldos_periodo
            $this->actualizarSaldosPeriodo($comprobanteId, (int)$cabecera['empresa_id'], $periodoId, $lineas);

            $pdo->commit();
            return $comprobanteId;

        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Anula un comprobante registrado (no lo elimina, mantiene auditoría).
     */
    public function anularComprobante(int $comprobanteId, int $usuarioId): bool
    {
        $pdo = $this->database->getPdo();

        $stmt = $pdo->prepare(
            "SELECT estado, empresa_id FROM comprobantes WHERE id = :id FOR UPDATE"
        );
        $stmt->execute([':id' => $comprobanteId]);
        $comp = $stmt->fetch();

        if (!$comp) {
            throw new \RuntimeException("Comprobante #{$comprobanteId} no encontrado.");
        }

        if ($comp['estado'] === 'anulado') {
            throw new \RuntimeException("El comprobante ya fue anulado anteriormente.");
        }

        $pdo->beginTransaction();
        try {
            // Revertir saldos
            $lineas = $this->obtenerLineasComprobante($comprobanteId);
            $this->revertirSaldosPeriodo($comprobanteId, (int)$comp['empresa_id'], $lineas);

            $pdo->prepare(
                "UPDATE comprobantes
                 SET estado = 'anulado', usuario_anula_id = :uid, fecha_anulacion = NOW()
                 WHERE id = :id"
            )->execute([':uid' => $usuarioId, ':id' => $comprobanteId]);

            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // ─── Métodos privados ─────────────────────────────────────────────────────

    /**
     * @param LineaAsiento[] $lineas
     */
    private function validarLineas(array $lineas): void
    {
        if (count($lineas) < 2) {
            throw new InvalidArgumentException(
                'El comprobante debe tener al menos 2 líneas (partida doble).'
            );
        }

        $totalDebito  = array_sum(array_map(fn(LineaAsiento $l) => $l->debito,  $lineas));
        $totalCredito = array_sum(array_map(fn(LineaAsiento $l) => $l->credito, $lineas));

        if (abs($totalDebito - $totalCredito) > self::TOLERANCIA) {
            throw new InvalidArgumentException(sprintf(
                'La partida doble no cuadra. Débitos: %s | Créditos: %s | Diferencia: %s',
                number_format($totalDebito,  2, '.', ','),
                number_format($totalCredito, 2, '.', ','),
                number_format(abs($totalDebito - $totalCredito), 2, '.', ',')
            ));
        }
    }

    private function obtenerPeriodoAbierto(int $empresaId, string $fecha): int
    {
        $dt  = new \DateTimeImmutable($fecha);
        $anio = (int)$dt->format('Y');
        $mes  = (int)$dt->format('n');

        $stmt = $this->database->getPdo()->prepare(
            "SELECT id, estado FROM periodos
             WHERE empresa_id = :eid AND anio = :anio AND mes = :mes
             LIMIT 1"
        );
        $stmt->execute([':eid' => $empresaId, ':anio' => $anio, ':mes' => $mes]);
        $periodo = $stmt->fetch();

        if (!$periodo) {
            throw new \RuntimeException("No existe periodo contable para {$mes}/{$anio}.");
        }

        if ($periodo['estado'] !== 'abierto') {
            throw new \RuntimeException(
                "El periodo {$mes}/{$anio} está cerrado o bloqueado. No se pueden registrar asientos."
            );
        }

        return (int)$periodo['id'];
    }

    private function siguienteConsecutivo(int $empresaId, int $tipoCompId): int
    {
        $pdo = $this->database->getPdo();
        $pdo->prepare(
            "UPDATE tipos_comprobante
             SET consecutivo_actual = consecutivo_actual + 1
             WHERE empresa_id = :eid AND id = :tid"
        )->execute([':eid' => $empresaId, ':tid' => $tipoCompId]);

        $stmt = $pdo->prepare(
            "SELECT consecutivo_actual FROM tipos_comprobante
             WHERE empresa_id = :eid AND id = :tid"
        );
        $stmt->execute([':eid' => $empresaId, ':tid' => $tipoCompId]);
        return (int)$stmt->fetchColumn();
    }

    private function validarCuentaAceptaMovimiento(int $cuentaId, int $empresaId): void
    {
        $stmt = $this->database->getPdo()->prepare(
            "SELECT acepta_movimiento, activa FROM puc_cuentas
             WHERE id = :id AND empresa_id = :eid"
        );
        $stmt->execute([':id' => $cuentaId, ':eid' => $empresaId]);
        $cuenta = $stmt->fetch();

        if (!$cuenta) {
            throw new InvalidArgumentException("Cuenta ID {$cuentaId} no existe en el PUC.");
        }
        if (!$cuenta['acepta_movimiento']) {
            throw new InvalidArgumentException(
                "La cuenta {$cuentaId} es de naturaleza agrupadora y no acepta movimientos directos."
            );
        }
        if (!$cuenta['activa']) {
            throw new InvalidArgumentException("La cuenta {$cuentaId} está inactiva.");
        }
    }

    /** @param LineaAsiento[] $lineas */
    private function actualizarSaldosPeriodo(
        int $comprobanteId,
        int $empresaId,
        int $periodoId,
        array $lineas
    ): void {
        $pdo  = $this->database->getPdo();
        $stmt = $pdo->prepare(
            "INSERT INTO saldos_periodo (empresa_id, periodo_id, cuenta_id, total_debito, total_credito)
             VALUES (:eid, :pid, :cid, :deb, :cre)
             ON DUPLICATE KEY UPDATE
                total_debito  = total_debito  + VALUES(total_debito),
                total_credito = total_credito + VALUES(total_credito)"
        );
        foreach ($lineas as $linea) {
            $stmt->execute([
                ':eid' => $empresaId,
                ':pid' => $periodoId,
                ':cid' => $linea->cuentaId,
                ':deb' => round($linea->debito, 4),
                ':cre' => round($linea->credito, 4),
            ]);
        }
    }

    private function obtenerLineasComprobante(int $comprobanteId): array
    {
        $stmt = $this->database->getPdo()->prepare(
            "SELECT cuenta_id, debito, credito FROM asientos WHERE comprobante_id = :id"
        );
        $stmt->execute([':id' => $comprobanteId]);
        return array_map(
            fn($row) => new LineaAsiento((int)$row['cuenta_id'], (float)$row['debito'], (float)$row['credito']),
            $stmt->fetchAll()
        );
    }

    private function revertirSaldosPeriodo(int $comprobanteId, int $empresaId, array $lineas): void
    {
        // Obtener periodo del comprobante
        $stmt = $this->database->getPdo()->prepare(
            "SELECT periodo_id FROM comprobantes WHERE id = :id"
        );
        $stmt->execute([':id' => $comprobanteId]);
        $periodoId = (int)$stmt->fetchColumn();

        $stmtUpd = $this->database->getPdo()->prepare(
            "UPDATE saldos_periodo
             SET total_debito  = total_debito  - :deb,
                 total_credito = total_credito - :cre
             WHERE empresa_id = :eid AND periodo_id = :pid AND cuenta_id = :cid"
        );
        foreach ($lineas as $linea) {
            $stmtUpd->execute([
                ':eid' => $empresaId,
                ':pid' => $periodoId,
                ':cid' => $linea->cuentaId,
                ':deb' => round($linea->debito, 4),
                ':cre' => round($linea->credito, 4),
            ]);
        }
    }
}
