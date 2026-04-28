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
    private $db;
    private $asientosTieneFecha;

    public function __construct()
    {
        $this->db = Database::getInstance()->getPdo();
        $this->asientosTieneFecha = $this->tableHasColumn('asientos', 'fecha');
    }

    public function getJournal($empresaId, $periodoId)
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

    public function getLedger($empresaId, $periodoId)
    {
        $periodo = $this->db->query("SELECT * FROM periodos WHERE id = $periodoId")->fetch();
        $mesAnterior = $periodo['mes'] - 1;
        $anioAnterior = $periodo['anio'];
        if ($mesAnterior == 0) {
            $mesAnterior = 12;
            $anioAnterior--;
        }

        $sql = "SELECT p.id as cuenta_id, p.codigo, p.nombre, p.naturaleza,
                       (SELECT COALESCE(SUM(ABS(debito) - ABS(credito)), 0) FROM asientos a2
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

    public function getComparativeBalance($empresaId, $year, $proyectoId = null)
    {
        $prevYear = $year - 1;

        $stmt = $this->db->prepare(
            "SELECT MIN(id) as id, codigo, nombre, naturaleza, nivel, tipo_cuenta
             FROM puc_cuentas
             WHERE empresa_id = :eid
               AND activa = 1
             GROUP BY codigo, nombre, naturaleza, nivel, tipo_cuenta
             ORDER BY codigo ASC"
        );
        $stmt->execute([':eid' => $empresaId]);
        $rawAccounts = $stmt->fetchAll();

        $accounts = [];
        $incomeExpensesBalancePrev = 0.0;
        $incomeExpensesBalanceCurr = 0.0;

        foreach ($rawAccounts as $acc) {
            $d1 = substr((string)$acc['codigo'], 0, 1);
            if ($d1 === '1') {
                $acc['tipo_cuenta'] = 'A';
            } elseif ($d1 === '2') {
                $acc['tipo_cuenta'] = 'P';
            } elseif ($d1 === '3') {
                $acc['tipo_cuenta'] = 'R';
            } elseif (in_array($d1, ['4','5','6','7'], true)) {
                $acc['tipo_cuenta'] = 'G';
            } else {
                continue;
            }
            $accounts[] = $acc;
        }

        $saldosPrev = $this->getBalanceSnapshotByAccount($empresaId, $prevYear, $proyectoId);
        $saldosCurr = $this->getBalanceSnapshotByAccount($empresaId, $year, $proyectoId);

        foreach ($accounts as $acc) {
            if ($acc['tipo_cuenta'] === 'G' && $acc['nivel'] >= 4) {
                $hasChildren = false;
                foreach ($accounts as $child) {
                    if ($child['id'] !== $acc['id'] && strpos((string)$child['codigo'], (string)$acc['codigo']) === 0) {
                        $hasChildren = true;
                        break;
                    }
                }
                if (!$hasChildren) {
                    $incomeExpensesBalancePrev += (isset($saldosPrev[$acc['codigo']]) ? $saldosPrev[$acc['codigo']] : 0.0);
                    $incomeExpensesBalanceCurr += (isset($saldosCurr[$acc['codigo']]) ? $saldosCurr[$acc['codigo']] : 0.0);
                }
            }
        }

        $rows = [];
        foreach ($accounts as $account) {
            if ($account['tipo_cuenta'] === 'G') continue;
            $codigo = (string) $account['codigo'];
            $saldoAnterior = 0.0;
            $saldoActual = 0.0;

            foreach ($saldosPrev as $childCode => $childSaldo) {
                if (strpos((string) $childCode, (string) $codigo) === 0) {
                    $saldoAnterior += (float) $childSaldo;
                }
            }

            foreach ($saldosCurr as $childCode => $childSaldo) {
                if (strpos((string) $childCode, (string) $codigo) === 0) {
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

        $utilPrev = $incomeExpensesBalancePrev * -1;
        $utilCurr = $incomeExpensesBalanceCurr * -1;
        
        if (abs($utilPrev) > 0.001 || abs($utilCurr) > 0.001) {
            $rows[] = [
                'cuenta_id' => 999999,
                'codigo' => '36999999',
                'nombre' => ($utilCurr >= 0 ? 'UTILIDAD' : 'PÉRDIDA') . ' DEL EJERCICIO',
                'naturaleza' => 'C',
                'nivel' => 4,
                'tipo_cuenta' => 'R',
                'saldo_anterior' => $utilPrev,
                'saldo_actual' => $utilCurr,
                'diferencia' => $utilCurr - $utilPrev,
                'porcentaje' => abs($utilPrev) > 0 ? (($utilCurr - $utilPrev) / abs($utilPrev)) * 100 : 0,
            ];
        }

        return $rows;
    }

    private function getBalanceSnapshotByAccount($empresaId, $year, $proyectoId = null)
    {
        $proyFilter = $proyectoId ? 'AND a.proyecto_id = :proy' : '';

        $sql = "SELECT 
                    p.id as cuenta_id,
                    p.codigo,
                    p.nombre,
                    p.naturaleza,
                    p.nivel,
                    p.tipo_cuenta,
                    COALESCE(SUM(
                        CASE 
                            WHEN c.id IS NOT NULL AND c.id NOT IN (10631, 10083)
                            THEN (a.debito - a.credito)
                            ELSE 0 
                        END
                    ), 0) AS saldo_operativo,
                    COALESCE(SUM(
                        CASE 
                            WHEN c.id IS NOT NULL AND c.id IN (10631, 10083)
                            THEN (a.debito - a.credito)
                            ELSE 0 
                        END
                    ), 0) AS saldo_migracion
                FROM puc_cuentas p
                LEFT JOIN asientos a 
                    ON a.cuenta_id = p.id 
                   AND a.empresa_id = p.empresa_id
                $proyFilter
                LEFT JOIN comprobantes c 
                    ON c.id = a.comprobante_id 
                   AND c.empresa_id = p.empresa_id
                   AND c.estado = 'registrado'
                   AND YEAR(c.fecha) <= :year
                WHERE p.empresa_id = :eid
                  AND p.activa = 1
                GROUP BY p.id, p.codigo, p.nombre, p.naturaleza, p.nivel, p.tipo_cuenta
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
        $rawRows = $stmt->fetchAll();

        $balances = [];
        foreach ($rawRows as $row) {
            $saldoOperativo = (float)$row['saldo_operativo'];
            $saldoMigracion = (float)$row['saldo_migracion'];

            if (abs($saldoMigracion) > 0.001) {
                if (abs($saldoOperativo - $saldoMigracion) < 0.1) {
                    $saldoFinal = $saldoOperativo;
                } else {
                    $saldoFinal = $saldoMigracion;
                }
            } else {
                $saldoFinal = $saldoOperativo;
            }

            $balances[(string) $row['codigo']] = $saldoFinal;
        }

        return $balances;
    }

    public function getIncomeStatement($empresaId, $year, $proyectoId = null)
    {
        $stmt = $this->db->prepare(
            "SELECT id, codigo, nombre, naturaleza, nivel, tipo_cuenta
             FROM puc_cuentas
             WHERE empresa_id = :eid 
               AND activa = 1 
               AND SUBSTR(codigo, 1, 1) IN ('4','5','6','7')
             ORDER BY codigo ASC"
        );
        $stmt->execute([':eid' => $empresaId]);
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $proyFilter = $proyectoId ? 'AND a.proyecto_id = :proy' : '';
        $sqlMovs = "SELECT p.codigo, SUM(a.debito - a.credito) as neto
                    FROM asientos a
                    JOIN comprobantes c ON a.comprobante_id = c.id
                    JOIN puc_cuentas p ON a.cuenta_id = p.id
                    WHERE a.empresa_id = :eid 
                      AND c.estado = 'registrado' 
                      AND c.fecha >= :startDate 
                      AND c.fecha <= :endDate
                      $proyFilter
                      AND SUBSTR(p.codigo, 1, 1) IN ('4','5','6','7')
                    GROUP BY p.codigo";
        
        $startDate = ($year - 1) . "-12-01";
        $endDate = $year . "-12-31";
        $params = [':eid' => $empresaId, ':startDate' => $startDate, ':endDate' => $endDate];
        if ($proyectoId) $params[':proy'] = $proyectoId;
        
        $stmtMovs = $this->db->prepare($sqlMovs);
        $stmtMovs->execute($params);
        $saldosNetos = [];
        foreach ($stmtMovs->fetchAll() as $sm) {
            $saldosNetos[$sm['codigo']] = (float)$sm['neto'];
        }

        $rows = [];
        foreach ($accounts as $acc) {
            $codigo = (string)$acc['codigo'];
            $saldoTotal = 0.0;

            foreach ($saldosNetos as $cCode => $cNeto) {
                if (strpos((string)$cCode, (string)$codigo) === 0) {
                    $saldoTotal += $cNeto;
                }
            }

            if (abs($saldoTotal) < 0.01) continue;

            if ($acc['naturaleza'] === 'C') {
                $saldoTotal *= -1;
            } else {
                $saldoTotal = abs($saldoTotal);
            }

            $rows[] = [
                'id' => $acc['id'],
                'codigo' => $codigo,
                'nombre' => $acc['nombre'],
                'nivel' => $acc['nivel'],
                'naturaleza' => $acc['naturaleza'],
                'saldo' => $saldoTotal,
                'tipo_cuenta' => substr($codigo, 0, 1)
            ];
        }

        return $rows;
    }

    private function tableHasColumn($table, $column)
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

    public function getInventoryBalances($empresaId, $year)
    {
        $sql = "SELECT p.id as cuenta_id, p.codigo, p.nombre, p.naturaleza, p.nivel, p.tipo_cuenta,
                       (SELECT COALESCE(SUM(a2.debito - a2.credito), 0) FROM asientos a2
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

    public function getFolioState($empresaId, $tipo)
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
