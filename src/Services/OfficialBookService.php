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
               AND activa = 1
             ORDER BY codigo ASC"
        );
        $stmt->execute([':eid' => $empresaId]);
        $rawAccounts = $stmt->fetchAll();

        // Clasificación estricta por primer dígito
        $accounts = [];
        $incomeExpensesBalancePrev = 0.0;
        $incomeExpensesBalanceCurr = 0.0;

        foreach ($rawAccounts as $acc) {
            $d1 = substr((string)$acc['codigo'], 0, 1);
            if ($d1 === '1') {
                $acc['tipo_cuenta'] = 'A'; // Activo
            } elseif ($d1 === '2') {
                $acc['tipo_cuenta'] = 'P'; // Pasivo
            } elseif ($d1 === '3') {
                $acc['tipo_cuenta'] = 'R'; // Patrimonio/Reserva
            } elseif (in_array($d1, ['4','5','6','7'], true)) {
                // Estas cuentas NO van directo al balance, se usan para calcular la UTILIDAD
                $acc['tipo_cuenta'] = 'G'; // Grupo de Resultados
            } else {
                continue;
            }
            $accounts[] = $acc;
        }

        $saldosPrev = $this->getBalanceSnapshotByAccount($empresaId, $prevYear, $proyectoId);
        $saldosCurr = $this->getBalanceSnapshotByAccount($empresaId, $year, $proyectoId);

        // Calcular utilidad neta antes de procesar filas
        foreach ($accounts as $acc) {
            if ($acc['tipo_cuenta'] === 'G' && $acc['nivel'] >= 4) {
                // Solo sumamos hojas para evitar duplicados
                $hasChildren = false;
                foreach ($accounts as $child) {
                    if ($child['id'] !== $acc['id'] && str_starts_with((string)$child['codigo'], (string)$acc['codigo'])) {
                        $hasChildren = true;
                        break;
                    }
                }
                if (!$hasChildren) {
                    $incomeExpensesBalancePrev += ($saldosPrev[$acc['codigo']] ?? 0.0);
                    $incomeExpensesBalanceCurr += ($saldosCurr[$acc['codigo']] ?? 0.0);
                }
            }
        }

        $rows = [];
        foreach ($accounts as $account) {
            if ($account['tipo_cuenta'] === 'G') continue; // Omitir ingresos/gastos del cuerpo del balance
            $codigo = (string) $account['codigo'];
            $saldoAnterior = 0.0;
            $saldoActual = 0.0;

            foreach ($saldosPrev as $childCode => $childSaldo) {
                if (str_starts_with((string) $childCode, (string) $codigo)) {
                    $saldoAnterior += (float) $childSaldo;
                }
            }

            foreach ($saldosCurr as $childCode => $childSaldo) {
                if (str_starts_with((string) $childCode, (string) $codigo)) {
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

        // Agregar fila virtual de Utilidad/Pérdida al final de la sección de Patrimonio
        // Agregar fila virtual de Utilidad/Pérdida al final de la sección de Patrimonio
        $utilPrev = $incomeExpensesBalancePrev * -1; // Invertimos para que sea crédito (positivo = utilidad)
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

    /**
     * Obtiene un corte acumulado por cuenta hasta el ano indicado.
     */
    private function getBalanceSnapshotByAccount(int $empresaId, int $year, ?int $proyectoId = null): array
    {
        // Usar c.fecha como fecha autoritativa del periodo.
        // COALESCE(a.fecha, c.fecha) causa que asientos importados con a.fecha=2023
        // pero cuyo comprobante tiene c.fecha=2024/2025 aparezcan en balances históricos.
        $fechaExpr = 'c.fecha';
        $proyFilter = $proyectoId ? 'AND a.proyecto_id = :proy' : '';

        $sql = "SELECT
                    p.codigo,
                    COALESCE(SUM(
                        CASE
                            WHEN c.id IS NOT NULL THEN (a.debito - a.credito)
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
                  AND p.activa = 1
                  AND SUBSTR(p.codigo, 1, 1) IN ('1','2','3','4','5','6')
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
        
        // DEBUG LOG SQL
        $logParams = json_encode($params);
        file_put_contents(__DIR__ . '/../../scratch/sql_debug.log', date('Y-m-d H:i:s') . " - SQL: $sql | Params: $logParams\n", FILE_APPEND);

        $stmt->execute($params);

        $balances = [];
        foreach ($stmt->fetchAll() as $row) {
            $balances[(string) $row['codigo']] = (float) $row['saldo_neto'];
        }

        return $balances;
    }

    /**
     * Obtiene el Estado de Resultados para un año específico con jerarquía completa.
     */
    public function getIncomeStatement(int $empresaId, int $year, ?int $proyectoId = null): array
    {
        // 1. Obtener todas las cuentas de resultados (4,5,6,7)
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

        // 2. Obtener movimientos netos del año
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

        // 3. Procesar saldos y jerarquía
        $rows = [];
        foreach ($accounts as $acc) {
            $codigo = (string)$acc['codigo'];
            $saldoTotal = 0.0;

            // 1. Sumar todos los saldos de hijos (o el propio si es hoja)
            foreach ($saldosNetos as $cCode => $cNeto) {
                if (str_starts_with((string)$cCode, (string)$codigo)) {
                    $saldoTotal += $cNeto;
                }
            }

            // 2. Omitir cuentas sin saldo para limpiar el reporte
            if (abs($saldoTotal) < 0.01) continue;

            // 3. Ajustar por naturaleza para que los saldos se vean como valores positivos en el reporte
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
