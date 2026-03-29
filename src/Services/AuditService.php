<?php
declare(strict_types=1);

namespace ContaFC\Services;

use ContaFC\Core\Database;
use PDO;
use Exception;

/**
 * Servicio de Auditoría - ContaFC (Honduras)
 * Control de log de transacciones y brechas en numeración (consecutivos).
 */
class AuditService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getPdo();
    }

    /**
     * Obtiene los logs de transacciones filtrados
     */
    public function getLogs(int $empresaId, array $filters = []): array
    {
        $where = ["l.empresa_id = :eid"];
        $params = [':eid' => $empresaId];

        if (!empty($filters['usuario_id'])) {
            $where[] = "l.usuario_id = :uid";
            $params[':uid'] = $filters['usuario_id'];
        }
        if (!empty($filters['tabla'])) {
            $where[] = "l.tabla = :tabla";
            $params[':tabla'] = $filters['tabla'];
        }
        if (!empty($filters['accion'])) {
            $where[] = "l.accion = :accion";
            $params[':accion'] = $filters['accion'];
        }
        if (!empty($filters['desde'])) {
            $where[] = "l.created_at >= :desde";
            $params[':desde'] = $filters['desde'] . ' 00:00:00';
        }
        if (!empty($filters['hasta'])) {
            $where[] = "l.created_at <= :hasta";
            $params[':hasta'] = $filters['hasta'] . ' 23:59:59';
        }

        $sql = "SELECT l.*, u.username as usuario_nombre 
                FROM audit_log l
                LEFT JOIN usuarios u ON l.usuario_id = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY l.created_at DESC LIMIT 500";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Detecta brechas (saltos) en la numeración de documentos
     * Útil para detectar facturas borradas o comprobantes eliminados de forma irregular.
     */
    public function checkConsecutiveGaps(int $empresaId, string $table = 'comprobantes', int $tipoCompId = null): array
    {
        if (!in_array($table, ['comprobantes', 'facturas'])) {
            throw new Exception("Tabla no auditable por consecutivos.");
        }

        $where = "empresa_id = :eid";
        $params = [':eid' => $empresaId];

        if ($tipoCompId) {
            $where .= " AND tipo_comp_id = :tid";
            $params[':tid'] = $tipoCompId;
        }

        // Obtener todos los números registrados por empresa/tipo
        $sql = "SELECT numero FROM $table WHERE $where ORDER BY numero ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $numeros = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($numeros)) return [];

        $gaps = [];
        $min = (int)min($numeros);
        $max = (int)max($numeros);

        for ($i = $min; $i <= $max; $i++) {
            if (!in_array($i, $numeros)) {
                $gaps[] = $i;
            }
        }

        return [
            'total_gaps' => count($gaps),
            'gaps'       => $gaps,
            'rango'      => ['min' => $min, 'max' => $max],
            'count'      => count($numeros)
        ];
    }
}
