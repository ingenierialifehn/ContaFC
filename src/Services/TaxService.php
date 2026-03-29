<?php
declare(strict_types=1);

namespace ContaFC\Services;

use ContaFC\Core\Database;
use PDO;
use Exception;

/**
 * Servicio de Impuestos y Certificados de Retención (Honduras)
 * SAR - ISV y Fuente.
 */
class TaxService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getPdo();
    }

    /**
     * Genera un nuevo certificado de retención
     */
    public function createCertificate(array $data): int
    {
        $this->db->beginTransaction();
        try {
            $eid = $data['empresa_id'];
            
            // 1. Generar número correlativo si no se provee
            $correlativo = $data['correlativo'] ?? null;
            if (!$correlativo) {
                $max = $this->db->query("SELECT MAX(CAST(correlativo AS UNSIGNED)) FROM certificados_retencion WHERE empresa_id = $eid")->fetchColumn();
                $correlativo = str_pad((string)((int)$max + 1), 6, '0', STR_PAD_LEFT);
            }

            // 2. Insertar certificado
            $sql = "INSERT INTO certificados_retencion (
                        empresa_id, tercero_id, comprobante_id, tipo_retencion_id, 
                        correlativo, fecha, base_imponible, porcentaje, monto_retencion
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $this->db->prepare($sql)->execute([
                $eid,
                $data['tercero_id'],
                $data['comprobante_id'] ?? null,
                $data['tipo_retencion_id'],
                $correlativo,
                $data['fecha'] ?? date('Y-m-d'),
                $data['base_imponible'],
                $data['porcentaje'],
                $data['monto_retencion']
            ]);

            $id = (int)$this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Obtiene los certificados emitidos de una empresa
     */
    public function getCertificates(int $empresaId): array
    {
        $sql = "SELECT c.*, t.razon_social as tercero_nom, t.rtn as tercero_rtn, tr.nombre as ret_nombre, tr.tipo as ret_tipo
                FROM certificados_retencion c
                JOIN terceros t ON c.tercero_id = t.id
                JOIN tipos_retencion tr ON c.tipo_retencion_id = tr.id
                WHERE c.empresa_id = :eid
                ORDER BY c.fecha DESC, c.correlativo DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':eid' => $empresaId]);
        return $stmt->fetchAll();
    }

    /**
     * Busca retenciones detectadas en asientos contables (mayor de retenciones)
     * Útil para sugerir certificados a emitir.
     */
    public function detectRetentionsInJournal(int $empresaId, string $desde, string $hasta): array
    {
        // Buscamos cuentas contables que suelen ser de retenciones (vía tipos_retencion)
        $sql = "SELECT a.*, c.fecha, c.numero as comp_num, t.razon_social as tercero_nom, tr.id as tipo_ret_id, tr.nombre as tipo_ret_nom
                FROM asientos a
                JOIN comprobantes c ON a.comprobante_id = c.id
                JOIN tipos_retencion tr ON a.cuenta_id = tr.cuenta_id
                JOIN terceros t ON a.tercero_id = t.id
                WHERE c.empresa_id = :eid AND c.fecha BETWEEN :desde AND :hasta
                AND a.id NOT IN (SELECT IFNULL(comprobante_id, 0) FROM certificados_retencion WHERE empresa_id = :eid)
                ORDER BY c.fecha ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':eid' => $empresaId, ':desde' => $desde, ':hasta' => $hasta]);
        return $stmt->fetchAll();
    }
}
