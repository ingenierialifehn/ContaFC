<?php
declare(strict_types=1);

namespace ContaFC\Services;

use ContaFC\Core\Database;
use PDO;

/**
 * CarteraService – Gestión de créditos, cuotas y recaudos.
 * Aplica amortización francesa (cuota fija) o lineal.
 */
class CarteraService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getPdo();
    }

    // ─── CRÉDITOS ─────────────────────────────────────────────────────────────

    public function getCreditos(int $empresaId, string $estado = 'activo'): array
    {
        $sql = "SELECT c.*, t.razon_social AS tercero_nombre, p.nombre AS proyecto_nombre
                FROM cartera_creditos c
                LEFT JOIN terceros t ON t.id = c.tercero_id
                LEFT JOIN proyectos p ON p.id = c.proyecto_id
                WHERE c.empresa_id = :eid AND c.estado = :estado
                ORDER BY c.created_at DESC";
        $st = $this->db->prepare($sql);
        $st->execute([':eid' => $empresaId, ':estado' => $estado]);
        return $st->fetchAll();
    }

    public function getCreditoById(int $id, int $empresaId): ?array
    {
        $st = $this->db->prepare(
            "SELECT c.*, t.razon_social AS tercero_nombre, p.nombre AS proyecto_nombre
             FROM cartera_creditos c
             LEFT JOIN terceros t ON t.id = c.tercero_id
             LEFT JOIN proyectos p ON p.id = c.proyecto_id
             WHERE c.id = :id AND c.empresa_id = :eid"
        );
        $st->execute([':id' => $id, ':eid' => $empresaId]);
        return $st->fetch() ?: null;
    }

    public function createCredito(array $d, int $empresaId): int
    {
        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare(
                "INSERT INTO cartera_creditos
                    (empresa_id, tercero_id, proyecto_id, referencia_doc, descripcion,
                     valor_total, saldo_actual, tasa_interes, cuotas_totales,
                     frecuencia, fecha_inicio, estado)
                 VALUES
                    (:eid, :tid, :pid, :ref, :desc,
                     :vt, :sa, :ti, :ct,
                     :frec, :fi, 'activo')"
            );
            $st->execute([
                ':eid'  => $empresaId,
                ':tid'  => (int)$d['tercero_id'],
                ':pid'  => !empty($d['proyecto_id']) ? (int)$d['proyecto_id'] : null,
                ':ref'  => $d['referencia_doc'] ?? '',
                ':desc' => $d['descripcion'] ?? '',
                ':vt'   => (float)$d['valor_total'],
                ':sa'   => (float)$d['valor_total'],      // saldo inicial = total
                ':ti'   => (float)($d['tasa_interes'] ?? 0),
                ':ct'   => (int)$d['cuotas_totales'],
                ':frec' => $d['frecuencia'] ?? 'mensual',
                ':fi'   => $d['fecha_inicio'],
            ]);
            $creditoId = (int)$this->db->lastInsertId();

            // Generar tabla de amortización
            $this->generarCuotas($creditoId, (float)$d['valor_total'],
                (float)($d['tasa_interes'] ?? 0), (int)$d['cuotas_totales'],
                $d['fecha_inicio'], $d['frecuencia'] ?? 'mensual');

            $this->db->commit();
            return $creditoId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function generarCuotas(int $creditoId, float $capital, float $tasaAnual,
                                    int $cuotas, string $fechaInicio, string $frecuencia): void
    {
        // Amortización francesa (cuota fija)
        $periodos = match($frecuencia) {
            'quincenal' => 24,
            'semanal'   => 52,
            default     => 12,
        };
        $tasaPeriodo = ($tasaAnual / 100) / $periodos;

        if ($tasaPeriodo > 0) {
            $cuotaFija = $capital * ($tasaPeriodo * pow(1 + $tasaPeriodo, $cuotas))
                         / (pow(1 + $tasaPeriodo, $cuotas) - 1);
        } else {
            $cuotaFija = $capital / $cuotas;
        }

        $saldo = $capital;
        $fecha = new \DateTime($fechaInicio);
        $daysInterval = match($frecuencia) {
            'quincenal' => 15,
            'semanal'   => 7,
            default     => 30,
        };
        $interval = new \DateInterval("P{$daysInterval}D");

        $stCuota = $this->db->prepare(
            "INSERT INTO cartera_cuotas
                (credito_id, num_cuota, fecha_vencimiento, valor_capital, valor_interes)
             VALUES (:cid, :num, :fv, :vc, :vi)"
        );

        for ($i = 1; $i <= $cuotas; $i++) {
            $fecha->add($interval);
            $interes  = $saldo * $tasaPeriodo;
            $capitalC = $cuotaFija - $interes;
            if ($i === $cuotas) $capitalC = $saldo; // ajuste última cuota
            $saldo -= $capitalC;

            $stCuota->execute([
                ':cid' => $creditoId,
                ':num' => $i,
                ':fv'  => $fecha->format('Y-m-d'),
                ':vc'  => round($capitalC, 2),
                ':vi'  => round($interes, 2),
            ]);
        }
    }

    public function deleteCredito(int $id, int $empresaId): bool
    {
        $st = $this->db->prepare(
            "UPDATE cartera_creditos SET estado='anulado'
             WHERE id=:id AND empresa_id=:eid"
        );
        $st->execute([':id' => $id, ':eid' => $empresaId]);
        return $st->rowCount() > 0;
    }

    // ─── CUOTAS ───────────────────────────────────────────────────────────────

    public function getCuotas(int $creditoId): array
    {
        $st = $this->db->prepare(
            "SELECT * FROM cartera_cuotas WHERE credito_id=:cid ORDER BY num_cuota"
        );
        $st->execute([':cid' => $creditoId]);
        return $st->fetchAll();
    }

    // ─── RECAUDOS ─────────────────────────────────────────────────────────────

    public function getRecaudos(int $empresaId, ?int $creditoId = null): array
    {
        $sql = "SELECT r.*, t.razon_social AS tercero_nombre
                FROM cartera_recaudos r
                LEFT JOIN terceros t ON t.id = r.tercero_id
                WHERE r.empresa_id = :eid";
        $params = [':eid' => $empresaId];
        $sql .= " ORDER BY r.fecha DESC LIMIT 100";
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function createRecaudo(array $d, int $empresaId): int
    {
        $this->db->beginTransaction();
        try {
            // 1. Insertar recaudo
            $st = $this->db->prepare(
                "INSERT INTO cartera_recaudos
                    (empresa_id, tercero_id, fecha, valor_total, glosa, metodo_pago)
                 VALUES (:eid, :tid, :fecha, :vt, :glosa, :mp)"
            );
            $st->execute([
                ':eid'   => $empresaId,
                ':tid'   => (int)$d['tercero_id'],
                ':fecha' => $d['fecha'],
                ':vt'    => (float)$d['valor_total'],
                ':glosa' => $d['glosa'] ?? '',
                ':mp'    => $d['metodo_pago'] ?? 'efectivo',
            ]);
            $recaudoId = (int)$this->db->lastInsertId();

            // 2. Aplicar el pago a las cuotas del crédito (por FIF0)
            if (!empty($d['credito_id'])) {
                $this->aplicarPago((int)$d['credito_id'], (float)$d['valor_total']);
            }

            $this->db->commit();
            return $recaudoId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function aplicarPago(int $creditoId, float $valorPago): void
    {
        $cuotas = $this->db->prepare(
            "SELECT * FROM cartera_cuotas
             WHERE credito_id=:cid AND estado IN ('pendiente','parcial','mora')
             ORDER BY num_cuota ASC"
        );
        $cuotas->execute([':cid' => $creditoId]);
        $pendientes = $cuotas->fetchAll();

        $saldoPago = $valorPago;

        $stUpd = $this->db->prepare(
            "UPDATE cartera_cuotas
             SET valor_pagado = valor_pagado + :vp,
                 estado = IF((valor_pagado + :vp2) >= (valor_capital + valor_interes), 'pagado',
                          IF((valor_pagado + :vp3) > 0, 'parcial', estado))
             WHERE id = :id"
        );

        foreach ($pendientes as $cuota) {
            if ($saldoPago <= 0) break;
            $saldoCuota = ($cuota['valor_capital'] + $cuota['valor_interes']) - $cuota['valor_pagado'];
            $pago = min($saldoPago, $saldoCuota);
            $stUpd->execute([':vp' => $pago, ':vp2' => $pago, ':vp3' => $pago, ':id' => $cuota['id']]);
            $saldoPago -= $pago;
        }

        // Recalcular saldo del crédito
        $resumen = $this->db->prepare(
            "SELECT SUM(valor_capital + valor_interes - valor_pagado) AS saldo_pend
             FROM cartera_cuotas WHERE credito_id=:cid AND estado != 'pagado'"
        );
        $resumen->execute([':cid' => $creditoId]);
        $saldoPend = (float)($resumen->fetchColumn() ?? 0);

        $this->db->prepare(
            "UPDATE cartera_creditos
             SET saldo_actual = :sa, estado = IF(:sa2 <= 0, 'liquidado', estado)
             WHERE id = :id"
        )->execute([':sa' => $saldoPend, ':sa2' => $saldoPend, ':id' => $creditoId]);
    }

    // ─── ESTADO DE CUENTA ─────────────────────────────────────────────────────

    public function getEstadoCuenta(int $creditoId, int $empresaId): array
    {
        $credito = $this->getCreditoById($creditoId, $empresaId);
        if (!$credito) return [];
        $cuotas  = $this->getCuotas($creditoId);
        return compact('credito', 'cuotas');
    }
}
