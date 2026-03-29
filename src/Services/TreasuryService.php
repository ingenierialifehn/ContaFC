<?php
declare(strict_types=1);

namespace ContaFC\Services;

use ContaFC\Core\Database;
use PDO;
use Exception;

/**
 * Servicio de Tesorería (Conciliaciones y Recurrencia) - Honduras
 */
class TreasuryService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getPdo();
    }

    /**
     * Crea una nueva conciliación para un banco en un período determinado.
     */
    public function openReconciliation(int $bancoCtaId, int $periodoId, float $saldoBanco, int $usuarioId): int
    {
        // 1. Verificar si ya existe conciliación para este banco-período
        $stmt = $this->db->prepare("SELECT id FROM conciliaciones WHERE banco_cuenta_id = ? AND periodo_id = ?");
        $stmt->execute([$bancoCtaId, $periodoId]);
        if ($stmt->fetch()) {
            throw new Exception("Ya existe una conciliación abierta para este banco en el período seleccionado.");
        }

        // 2. Obtener saldo contable (libros) de la cuenta vinculada al banco
        $banco = $this->db->query("SELECT cuenta_id FROM bancos_cuentas WHERE id = $bancoCtaId")->fetch();
        if (!$banco) throw new Exception("Cuenta bancaria no encontrada.");

        $periodo = $this->db->query("SELECT anio, mes FROM periodos WHERE id = $periodoId")->fetch();
        $fechaCorte = date('Y-m-t', strtotime("{$periodo['anio']}-{$periodo['mes']}-01"));

        // Calcular saldo en libros al corte
        $stmtSaldo = $this->db->prepare("
            SELECT COALESCE(SUM(debito - credito), 0) FROM asientos a
            JOIN comprobantes c ON a.comprobante_id = c.id
            WHERE a.cuenta_id = ? AND c.fecha <= ? AND c.estado = 'registrado'
        ");
        $stmtSaldo->execute([$banco['cuenta_id'], $fechaCorte]);
        $saldoLibros = (float)$stmtSaldo->fetchColumn();

        // 3. Crear conciliación
        $sql = "INSERT INTO conciliaciones (banco_cuenta_id, periodo_id, fecha_corte, saldo_banco, saldo_libros, usuario_id) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $this->db->prepare($sql)->execute([$bancoCtaId, $periodoId, $fechaCorte, $saldoBanco, $saldoLibros, $usuarioId]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Obtiene los movimientos pendientes de conciliar (asientos de la cuenta del banco)
     */
    public function getPendingMovements(int $conciliacionId): array
    {
        $conc = $this->db->query("SELECT * FROM conciliaciones WHERE id = $conciliacionId")->fetch();
        if (!$conc) throw new Exception("Conciliación no encontrada.");

        $banco = $this->db->query("SELECT cuenta_id FROM bancos_cuentas WHERE id = {$conc['banco_cuenta_id']}")->fetch();
        
        // Movimientos del mayor de la cuenta bancaria hasta la fecha de corte
        $sql = "SELECT a.*, c.tipo_comp_id, tc.codigo as tipo_comp, c.numero, c.fecha, c.observaciones as cab_obs, t.razon_social as tercero
                FROM asientos a
                JOIN comprobantes c ON a.comprobante_id = c.id
                JOIN tipos_comprobante tc ON c.tipo_comp_id = tc.id
                LEFT JOIN terceros t ON a.tercero_id = t.id
                WHERE a.cuenta_id = :cid AND c.fecha <= :corte AND c.estado = 'registrado'
                AND a.id NOT IN (SELECT asiento_id FROM conciliaciones_det WHERE conciliacion_id = :concid)
                ORDER BY c.fecha ASC, c.numero ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cid' => $banco['cuenta_id'],
            ':corte' => $conc['fecha_corte'],
            ':concid' => $conciliacionId
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Procesa la ejecución de comprobantes recurrentes para un período.
     */
    public function processRecurrents(int $empresaId, int $periodoId, int $usuarioId): int
    {
        $recurrentes = $this->db->prepare("SELECT * FROM comprobantes_recurrentes WHERE empresa_id = ? AND activa = 1");
        $recurrentes->execute([$empresaId]);
        $plantillas = $recurrentes->fetchAll();

        $periodo = $this->db->query("SELECT * FROM periodos WHERE id = $periodoId")->fetch();
        if (!$periodo || $periodo['estado'] !== 'abierto') throw new Exception("Período no apto.");

        $count = 0;
        foreach ($plantillas as $p) {
            $data = json_decode($p['json_data'], true);
            
            // Simular creación de asiento
            // 1. Cabecera
            $tipoCode = $data['tipo_codigo'] ?? 'NA';
            $tipoID = $this->db->query("SELECT id FROM tipos_comprobante WHERE codigo = '$tipoCode' AND empresa_id = $empresaId")->fetchColumn();
            $numero = (int)$this->db->query("SELECT MAX(numero) FROM comprobantes WHERE empresa_id = $empresaId AND tipo_comp_id = $tipoID")->fetchColumn() + 1;
            $fecha = date('Y-m-d', strtotime("{$periodo['anio']}-{$periodo['mes']}-{$p['dia_ejecucion']}"));

            $this->db->beginTransaction();
            try {
                $stmtComp = $this->db->prepare("INSERT INTO comprobantes (empresa_id, tipo_comp_id, numero, fecha, periodo_id, observaciones, estado, usuario_id) 
                                                VALUES (?, ?, ?, ?, ?, ?, 'registrado', ?)");
                $stmtComp->execute([$empresaId, $tipoID, $numero, $fecha, $periodoId, $p['nombre'] . ' (Recurrente)', $usuarioId]);
                $compId = $this->db->lastInsertId();

                foreach ($data['lineas'] as $i => $l) {
                    $stmtAsiento = $this->db->prepare("INSERT INTO asientos (comprobante_id, empresa_id, linea, cuenta_id, tercero_id, ceco_id, debito, credito, descripcion) 
                                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtAsiento->execute([
                        $compId, $empresaId, $i + 1, $l['cuenta_id'], $l['tercero_id'] ?? null, $l['ceco_id'] ?? null, 
                        $l['debito'] ?? 0, $l['credito'] ?? 0, $l['descripcion'] ?? $p['nombre']
                    ]);
                }

                $this->db->prepare("UPDATE comprobantes_recurrentes SET ultimo_procesado = ? WHERE id = ?")
                          ->execute([$fecha, $p['id']]);

                $this->db->commit();
                $count++;
            } catch (Exception $e) {
                $this->db->rollBack();
                // Omitimos fallos individuales para seguir con el lote
            }
        }
        return $count;
    }
}
