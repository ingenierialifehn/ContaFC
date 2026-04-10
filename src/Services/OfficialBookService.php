<?php
declare(strict_types=1);

namespace ContaFC\Services;

use ContaFC\Core\Database;
use PDO;

/**
 * Servicio de Libros Oficiales (Honduras)
 * Libro Diario, Mayor y Balances.
 */
class OfficialBookService
{
    private PDO $db;
    private bool $asientosTieneFecha;

    public function __construct()
    {
        $this->db = Database::getInstance()->getPdo();
        $this->asientosTieneFecha = $this->tableHasColumn('asientos', 'fecha');
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
        $periodo = $this->db->query("SELECT * FROM periodos WHERE id = $periodoId")->fetch();
        $mesAnterior = $periodo['mes'] - 1;
        $anioAnterior = $periodo['anio'];
        if ($mesAnterior == 0) {
            $mesAnterior = 12;
            $anioAnterior--;
        }

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

        $periodoInicio = "{$periodo['anio']}-" . str_pad((string) $periodo['mes'], 2, '0', STR_PAD_LEFT) . "-01";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':eid' => $empresaId, ':pid' => $periodoId, ':periodo_inicio' => $periodoInicio]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene el Balance General Comparativo para un ano y el anterior con jerarquia.
     */
    public function getComparativeBalance(int $empresaId, int $year, ?int $proyectoId = null): array
    {
        $prevYear = $year - 1;

        $stmt = $this->db->prepare(
            "SELECT id, codigo, nombre, naturaleza, nivel, tipo_cuenta
             FROM puc_cuentas
             WHERE empresa_id = :eid
               AND tipo_cuenta IN ('A','P','R')
               AND activa = 1
             ORDER BY codigo ASC"
        );
        $stmt->execute([':eid' => $empresaId]);
        $accounts = $stmt->fetchAll();

        $saldosPrev = $this->getBalanceSnapshotByAccount($empresaId, $prevYear, $proyectoId);
        $saldosCurr = $this->getBalanceSnapshotByAccount($empresaId, $year, $proyectoId);

        $rows = [];
        foreach ($accounts as $account) {
            $codigo = (string) $account['codigo'];
            $saldoAnterior = 0.0;
            $saldoActual = 0.0;

            foreach ($saldosPrev as $childCode => $childSaldo) {
                if (str_starts_with((string) $childCode, $codigo)) {
                    $saldoAnterior += (float) $childSaldo;
                }
            }

            foreach ($saldosCurr as $childCode => $childSaldo) {
                if (str_starts_with((string) $childCode, $codigo)) {
                    $saldoActual += (float) $childSaldo;
                }
            }

            if (abs($saldoAnterior) <= 0.001 && abs($saldoActual) <= 0.001) {
                continue;
            }

            if ($account['naturaleza'] === 'C') {
                $saldoAnterior *= -1;
                $saldoActual *= -1;
            }

            $diferencia = $saldoActual - $saldoAnterior;
            $base = abs($saldoAnterior);

            $rows[] = [
                'cuenta_id' => $account['id'],
                'codigo' => $codigo,
                'nombre' => $account['nombre'],
                'naturaleza' => $account['naturaleza'],
                'nivel' => $account['nivel'],
                'tipo_cuenta' => $account['tipo_cuenta'],
                'saldo_anterior' => $saldoAnterior,
                'saldo_actual' => $saldoActual,
                'diferencia' => $diferencia,
                'porcentaje' => $base > 0 ? ($diferencia / $base) * 100 : 0,
            ];
        }

        return $rows;
    }

    /**
     * Obtiene un corte acumulado por cuenta hasta el ano indicado.
     */
    private function getBalanceSnapshotByAccount(int $empresaId, int $year, ?int $proyectoId = null): array
    {
        $fechaExpr = $this->asientosTieneFecha ? 'COALESCE(a.fecha, c.fecha)' : 'c.fecha';
        $proyFilter = $proyectoId ? 'AND a.proyecto_id = :proy' : '';

        $sql = "SELECT
                    p.codigo,
                    COALESCE(SUM(
                        CASE
                            WHEN c.id IS NOT NULL THEN a.debito - a.credito
                            ELSE 0
                        END
                    ), 0) AS saldo_neto
                FROM puc_cuentas p
                LEFT JOIN asientos a
                    ON a.cuenta_id = p.id
                   AND a.empresa_id = p.empresa_id
                   $proyFilter
                LEFT JOIN comprobantes c
                    ON c.id = a.comprobante_id
                   AND c.empresa_id = p.empresa_id
                   AND c.estado = 'registrado'
                   AND YEAR($fechaExpr) <= :year
                WHERE p.empresa_id = :eid
                  AND p.tipo_cuenta IN ('A','P','R')
                  AND p.activa = 1
                GROUP BY p.id, p.codigo
                HAVING ABS(saldo_neto) > 0.001
                ORDER BY p.codigo ASC";

        $params = [
            ':eid' => $empresaId,
            ':year' => $year,
        ];
        if ($proyectoId) {
            $params[':proy'] = $proyectoId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $balances = [];
        foreach ($stmt->fetchAll() as $row) {
            $balances[(string) $row['codigo']] = (float) $row['saldo_neto'];
        }

        return $balances;
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table
               AND COLUMN_NAME = :column
             LIMIT 1"
        );
        $stmt->execute([
            ':table' => $table,
            ':column' => $column,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Obtiene el Libro de Inventarios y Balances para un ano determinado
     */
    public function getInventoryBalances(int $empresaId, int $year): array
    {
        $sql = "SELECT p.id as cuenta_id, p.codigo, p.nombre, p.naturaleza, p.nivel, p.tipo_cuenta,
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
