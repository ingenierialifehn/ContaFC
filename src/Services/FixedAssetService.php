<?php
declare(strict_types=1);

namespace ContaFC\Services;

use ContaFC\Core\Database;
use PDO;
use Exception;

/**
 * Servicio para la gestión de Activos Fijos y Depreciaciones (Honduras)
 * Cumple con NIIF para PYMES.
 */
class FixedAssetService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getPdo();
    }

    /**
     * Registra un nuevo activo fijo
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO activos_fijos (
                    empresa_id, sucursal_id, codigo, nombre, descripcion, fecha_compra,
                    costo_adquisicion, valor_salvamento, vida_util_meses,
                    cuenta_activo_id, cuenta_deprec_acum_id, cuenta_gasto_deprec_id, ceco_id
                ) VALUES (
                    :empresa_id, :sucursal_id, :codigo, :nombre, :descripcion, :fecha_compra,
                    :costo_adquisicion, :valor_salvamento, :vida_util_meses,
                    :cuenta_activo_id, :cuenta_deprec_acum_id, :cuenta_gasto_deprec_id, :ceco_id
                )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':empresa_id'            => $data['empresa_id'],
            ':sucursal_id'           => $data['sucursal_id'] ?? null,
            ':codigo'                => $data['codigo'],
            ':nombre'                => $data['nombre'],
            ':descripcion'           => $data['descripcion'] ?? null,
            ':fecha_compra'          => $data['fecha_compra'],
            ':costo_adquisicion'     => $data['costo_adquisicion'],
            ':valor_salvamento'      => $data['valor_salvamento'] ?? 0,
            ':vida_util_meses'       => $data['vida_util_meses'],
            ':cuenta_activo_id'      => $data['cuenta_activo_id'],
            ':cuenta_deprec_acum_id' => $data['cuenta_deprec_acum_id'],
            ':cuenta_gasto_deprec_id' => $data['cuenta_gasto_deprec_id'],
            ':ceco_id'               => $data['ceco_id'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Obtiene todos los activos de una empresa
     */
    public function getAll(int $empresaId): array
    {
        $sql = "SELECT a.*, 
                       c1.codigo as cta_activo_cod, c1.nombre as cta_activo_nom,
                       c2.codigo as cta_dep_cod, c2.nombre as cta_dep_nom,
                       c3.codigo as cta_gas_cod, c3.nombre as cta_gas_nom,
                       ce.nombre as ceco_nom
                FROM activos_fijos a
                JOIN puc_cuentas c1 ON a.cuenta_activo_id = c1.id
                JOIN puc_cuentas c2 ON a.cuenta_deprec_acum_id = c2.id
                JOIN puc_cuentas c3 ON a.cuenta_gasto_deprec_id = c3.id
                LEFT JOIN centros_costo ce ON a.ceco_id = ce.id
                WHERE a.empresa_id = :eid
                ORDER BY a.codigo ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':eid' => $empresaId]);
        return $stmt->fetchAll();
    }

    /**
     * Procesa la depreciación mensual de todos los activos activos
     */
    public function processMonthlyDepreciation(int $empresaId, int $periodoId, int $usuarioId): array
    {
        // 1. Obtener el periodo
        $periodo = $this->db->query("SELECT * FROM periodos WHERE id = $periodoId")->fetch();
        if (!$periodo || $periodo['estado'] !== 'abierto') {
            throw new Exception("El período contable no existe o está cerrado.");
        }

        // 2. Obtener activos pendientes de depreciar en este periodo
        $sql = "SELECT * FROM activos_fijos 
                WHERE empresa_id = :eid AND estado = 'activo'
                AND id NOT IN (SELECT activo_id FROM activos_depreciaciones WHERE periodo_id = :pid)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':eid' => $empresaId, ':pid' => $periodoId]);
        $activos = $stmt->fetchAll();

        if (empty($activos)) {
            return ['success' => true, 'count' => 0, 'message' => 'No hay activos pendientes de depreciar en este periodo.'];
        }

        $this->db->beginTransaction();
        try {
            $totalDepreciado = 0;
            $count = 0;

            foreach ($activos as $activo) {
                $monto = (float)$activo['depreciacion_mensual']; // Se hereda de la columna generada
                
                // Si el valor restante es menor que la depreciación mensual, solo depreciar el resto
                $valorLibros = (float)$activo['costo_adquisicion'] - (float)$activo['depreciacion_acumulada'];
                if ($valorLibros <= (float)$activo['valor_salvamento']) continue;

                if ($valorLibros - $monto < (float)$activo['valor_salvamento']) {
                    $monto = $valorLibros - (float)$activo['valor_salvamento'];
                }

                if ($monto <= 0) continue;

                // Registrar en log de activos_depreciaciones
                $stmtDep = $this->db->prepare("INSERT INTO activos_depreciaciones (activo_id, periodo_id, valor) VALUES (?, ?, ?)");
                $stmtDep->execute([$activo['id'], $periodoId, $monto]);

                // Actualizar acumulado en activo_fijo
                $this->db->prepare("UPDATE activos_fijos SET depreciacion_acumulada = depreciacion_acumulada + ? WHERE id = ?")
                          ->execute([$monto, $activo['id']]);

                // Registrar asiento contable (opcionalmente simplificado aquí o agrupado)
                // Para este One-by-One, generaremos un asiento por cada activo para trazabilidad perfecta
                $this->generarAsientoDepreciacion($activo, $monto, $periodo, $usuarioId);

                $totalDepreciado += $monto;
                $count++;
            }

            $this->db->commit();
            return ['success' => true, 'count' => $count, 'total' => $totalDepreciado];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function generarAsientoDepreciacion(array $activo, float $monto, array $periodo, int $usuarioId): void
    {
        // 1. Crear Cabecera del Comprobante (Tipo: AJ - Ajuste o NA - Nota de Ajuste)
        $tipoID = $this->db->query("SELECT id FROM tipos_comprobante WHERE codigo = 'NA' AND empresa_id = {$activo['empresa_id']}")->fetchColumn();
        if (!$tipoID) {
            // Si no existe NA, buscamos el primero disponible
            $tipoID = $this->db->query("SELECT id FROM tipos_comprobante WHERE empresa_id = {$activo['empresa_id']} LIMIT 1")->fetchColumn();
        }

        $numero = (int)$this->db->query("SELECT MAX(numero) FROM comprobantes WHERE empresa_id = {$activo['empresa_id']} AND tipo_comp_id = $tipoID")->fetchColumn() + 1;

        $stmtComp = $this->db->prepare("INSERT INTO comprobantes (empresa_id, tipo_comp_id, numero, fecha, periodo_id, observaciones, estado, usuario_id) 
                                        VALUES (?, ?, ?, ?, ?, ?, 'registrado', ?)");
        $fecha = date('Y-m-t', strtotime("{$periodo['anio']}-{$periodo['mes']}-01")); // Último día del mes
        $stmtComp->execute([
            $activo['empresa_id'], 
            $tipoID, 
            $numero, 
            $fecha, 
            $periodo['id'], 
            "Depreciación Mensual Activo: {$activo['nombre']} ({$activo['codigo']})",
            $usuarioId
        ]);
        $compId = $this->db->lastInsertId();

        // 2. Partida Doble: Gasto contra Depreciación Acumulada
        // FILA 1: GASTO (DEBITO)
        $stmtAsiento = $this->db->prepare("INSERT INTO asientos (comprobante_id, empresa_id, linea, cuenta_id, ceco_id, debito, credito, descripcion) 
                                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtAsiento->execute([$compId, $activo['empresa_id'], 1, $activo['cuenta_gasto_deprec_id'], $activo['ceco_id'], $monto, 0, "Gasto Depreciación Activo"]);

        // FILA 2: DEPRECIACIÓN ACUMULADA (CREDITO)
        $stmtAsiento->execute([$compId, $activo['empresa_id'], 2, $activo['cuenta_deprec_acum_id'], null, 0, $monto, "Depreciación Acumulada"]);
    }
}
