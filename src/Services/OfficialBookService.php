<?php
declare(strict_types=1);

namespace ContaFC\Services;

use ContaFC\Core\Database;
use PDO;
use Exception;

/**
 * Servicio de Libros Oficiales (Honduras)
 * Libro Diario, Mayor y Balances.
 */
class OfficialBookService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getPdo();
    }

    /**
     * Obtiene el Libro Diario para un periodo determinado
     */
    public function getJournal(int $empresaId, int $periodoId): array
    {
        $sql = "SELECT c.numero, c.fecha, c.observaciones as cab_obs, tc.codigo as tipo_doc,
                       a.linea, p.codigo as cuenta_cod, p.nombre as cuenta_nom, 
                       a.debito, a.credito, a.descripcion as det_obs,
                       t.razon_social as tercero_nom
                FROM comprobantes c
                JOIN tipos_comprobante tc ON c.tipo_comp_id = tc.id
                JOIN asientos a ON c.id = a.comprobante_id
                JOIN puc_cuentas p ON a.cuenta_id = p.id
                LEFT JOIN terceros t ON a.tercero_id = t.id
                WHERE c.empresa_id = :eid AND c.periodo_id = :pid AND c.estado = 'registrado'
                ORDER BY c.fecha ASC, c.numero ASC, a.linea ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':eid' => $empresaId, ':pid' => $periodoId]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene el Libro Mayor para un periodo determinado
     */
    public function getLedger(int $empresaId, int $periodoId): array
    {
        // 1. Obtener periodo anterior para saldos iniciales
        $periodo = $this->db->query("SELECT * FROM periodos WHERE id = $periodoId")->fetch();
        $mesAnterior = $periodo['mes'] - 1;
        $anioAnterior = $periodo['anio'];
        if ($mesAnterior == 0) { $mesAnterior = 12; $anioAnterior--; }

        $idAnt = $this->db->query("SELECT id FROM periodos WHERE empresa_id = $empresaId AND anio = $anioAnterior AND mes = $mesAnterior")->fetchColumn();

        // 2. Query de saldos acumulados por cuenta
        $sql = "SELECT p.id as cuenta_id, p.codigo, p.nombre, p.naturaleza,
                       (SELECT COALESCE(SUM(debito - credito), 0) FROM asientos a2 
                        JOIN comprobantes c2 ON a2.comprobante_id = c2.id 
                        WHERE a2.cuenta_id = p.id AND c2.fecha < :periodo_inicio AND c2.estado = 'registrado') as saldo_anterior,
                       SUM(a.debito) as debitos_mes,
                       SUM(a.credito) as creditos_mes
                FROM puc_cuentas p
                LEFT JOIN asientos a ON p.id = a.cuenta_id
                LEFT JOIN comprobantes c ON a.comprobante_id = c.id AND c.periodo_id = :pid AND c.estado = 'registrado'
                WHERE p.empresa_id = :eid AND p.nivel <= 4
                GROUP BY p.id
                HAVING (saldo_anterior <> 0 OR debitos_mes <> 0 OR creditos_mes <> 0)
                ORDER BY p.codigo ASC";
        
        $periodoInicio = "{$periodo['anio']}-" . str_pad((string)$periodo['mes'], 2, '0', STR_PAD_LEFT) . "-01";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':eid' => $empresaId, ':pid' => $periodoId, ':periodo_inicio' => $periodoInicio]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene el Libro de Inventarios y Balances para un año determinado
     */
    public function getInventoryBalances(int $empresaId, int $year): array
    {
        $sql = "SELECT p.id as cuenta_id, p.codigo, p.nombre, p.naturaleza,
                       (SELECT COALESCE(SUM(debito - credito), 0) FROM asientos a2 
                        JOIN comprobantes c2 ON a2.comprobante_id = c2.id 
                        WHERE a2.cuenta_id = p.id AND YEAR(c2.fecha) < :y1 AND c2.estado = 'registrado') as saldo_anterior,
                       SUM(a.debito) as debitos_anio,
                       SUM(a.credito) as creditos_anio
                FROM puc_cuentas p
                LEFT JOIN asientos a ON p.id = a.cuenta_id
                LEFT JOIN comprobantes c ON a.comprobante_id = c.id AND YEAR(c.fecha) = :y2 AND c.estado = 'registrado'
                WHERE p.empresa_id = :eid AND p.nivel <= 4
                GROUP BY p.id
                HAVING (saldo_anterior <> 0 OR debitos_anio <> 0 OR creditos_anio <> 0)
                ORDER BY p.codigo ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':eid' => $empresaId, ':y1' => $year, ':y2' => $year]);
        return $stmt->fetchAll();
    }

    /**
     * Retorna y actualiza el folio de un libro oficial
     */
    public function getFolioState(int $empresaId, string $tipo): array
    {
        $stmt = $this->db->prepare("SELECT * FROM libros_folios WHERE empresa_id = ? AND libro_tipo = ?");
        $stmt->execute([$empresaId, $tipo]);
        $row = $stmt->fetch();

        if (!$row) {
             $this->db->prepare("INSERT INTO libros_folios (empresa_id, libro_tipo) VALUES (?, ?)")->execute([$empresaId, $tipo]);
             return ['folio_inicial' => 1, 'ultimo_folio_usado' => 0];
        }
        return $row;
    }
}
