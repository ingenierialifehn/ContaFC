<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requirePermiso('reportes');

header('Content-Type: application/json');

$db  = Database::getInstance()->getPdo();
$eid = Auth::empresaId();
$tipo = $_GET['tipo'] ?? 'balance_comprobacion';
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');
$soloMov = (int)($_GET['solo_mov'] ?? 0);

try {
    if ($tipo === 'balance_comprobacion') {
        // Obtenemos todas las cuentas de la empresa
        $stmtC = $db->prepare("SELECT * FROM puc_cuentas WHERE empresa_id = :eid ORDER BY codigo ASC");
        $stmtC->execute([':eid' => $eid]);
        $cuentas = $stmtC->fetchAll(PDO::FETCH_ASSOC);

        // Obtenemos los saldos acumulados en el rango para las cuentas de movimiento
        $stmtS = $db->prepare(
            "SELECT a.cuenta_id, 
                    SUM(a.debito) as deb, 
                    SUM(a.credito) as cre
             FROM asientos a
             JOIN comprobantes c ON a.comprobante_id = c.id
             WHERE c.empresa_id = :eid AND c.fecha BETWEEN :d AND :h AND c.estado = 'registrado'
             GROUP BY a.cuenta_id"
        );
        $stmtS->execute([':eid' => $eid, ':d' => $desde, ':h' => $hasta]);
        $saldos = [];
        while ($row = $stmtS->fetch(PDO::FETCH_ASSOC)) {
            $saldos[$row['cuenta_id']] = $row;
        }

        $res = [];
        foreach ($cuentas as $c) {
            $deb = (float)($saldos[$c['id']]['deb'] ?? 0);
            $cre = (float)($saldos[$c['id']]['cre'] ?? 0);
            
            // Si es cuenta de mayor (no acepta movimiento), debemos sumar hijos
            if ($c['acepta_movimiento'] == 0) {
                $sums = sumarHijos($cuentas, $saldos, $c['codigo']);
                $deb = $sums['deb'];
                $cre = $sums['cre'];
            }

            if ($soloMov && $deb == 0 && $cre == 0) continue;

            $saldo = ($c['naturaleza'] === 'D') ? ($deb - $cre) : ($cre - $deb);

            $res[] = [
                'id'            => $c['id'],
                'codigo'        => $c['codigo'],
                'nombre'        => $c['nombre'],
                'nivel'         => $c['nivel'],
                'total_debito'  => $deb,
                'total_credito' => $cre,
                'saldo'         => $saldo
            ];
        }
        echo json_encode(['data' => $res]);
    }
    elseif ($tipo === 'balance_general') {
        // Similar a comprobación pero solo Clases 1, 2, 3
        $stmt = $db->prepare(
            "SELECT c.codigo, c.nombre, c.nivel, c.naturaleza, c.tipo_cuenta,
                    (SELECT SUM(a.debito) FROM asientos a JOIN comprobantes comp ON a.comprobante_id = comp.id 
                     WHERE a.cuenta_id = c.id AND comp.empresa_id = :eid AND comp.fecha <= :h AND comp.estado = 'registrado') as deb,
                    (SELECT SUM(a.credito) FROM asientos a JOIN comprobantes comp ON a.comprobante_id = comp.id 
                     WHERE a.cuenta_id = c.id AND comp.empresa_id = :eid AND comp.fecha <= :h AND comp.estado = 'registrado') as cre
             FROM puc_cuentas c
             WHERE c.empresa_id = :eid AND c.tipo_cuenta IN ('A','P','R')
             ORDER BY c.codigo ASC"
        );
        $stmt->execute([':eid' => $eid, ':h' => $hasta]);
        $cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $res = [];
        foreach ($cuentas as $c) {
            $deb = (float)($c['deb'] ?? 0);
            $cre = (float)($c['cre'] ?? 0);
            
            // Lógica simplificada: Cuentas de nivel bajo o mayor
            $saldo = ($c['naturaleza'] === 'D') ? ($deb - $cre) : ($cre - $deb);
            if ($soloMov && $saldo == 0) continue;

            $res[] = [
                'codigo' => $c['codigo'],
                'nombre' => $c['nombre'],
                'nivel'  => $c['nivel'],
                'tipo_cuenta' => $c['tipo_cuenta'],
                'saldo_neto' => $saldo
            ];
        }
        echo json_encode(['data' => $res]);
    }
    elseif ($tipo === 'isv_report') {
        // Reporte especializado de ISV Honduras 15/18%
        // Buscamos asientos que afecten cuentas de ISV (común 1106 o 2103)
        $stmt = $db->prepare(
            "SELECT c.fecha, c.numero, tc.codigo as tipo, t.nombre as tercero, t.nit_cc as rtn,
                    a.descripcion, a.debito, a.credito, cu.codigo as cuenta_cod
             FROM asientos a
             JOIN comprobantes c ON a.comprobante_id = c.id
             JOIN tipos_comprobante tc ON c.tipo_comp_id = tc.id
             JOIN puc_cuentas cu ON a.cuenta_id = cu.id
             LEFT JOIN terceros t ON a.tercero_id = t.id
             WHERE c.empresa_id = :eid AND c.fecha BETWEEN :d AND :h 
               AND (cu.codigo LIKE '1106%' OR cu.codigo LIKE '2103%' OR a.descripcion LIKE '%ISV%')
               AND c.estado = 'registrado'
             ORDER BY c.fecha ASC"
        );
        $stmt->execute([':eid' => $eid, ':d' => $desde, ':h' => $hasta]);
        echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function sumarHijos($todas, $saldos, $codPadre) {
    $deb = 0; $cre = 0;
    foreach ($todas as $c) {
        if (strpos($c['codigo'], $codPadre) === 0 && $c['acepta_movimiento'] == 1) {
            $deb += (float)($saldos[$c['id']]['deb'] ?? 0);
            $cre += (float)($saldos[$c['id']]['cre'] ?? 0);
        }
    }
    return ['deb' => $deb, 'cre' => $cre];
}
