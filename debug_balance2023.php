<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Database;

$db  = Database::getInstance()->getPdo();
$eid  = 1;
$year = 2023;

// Buscar el proyecto "Residencial Villa Francis"
$stmtProy = $db->prepare("SELECT id, nombre FROM proyectos WHERE empresa_id = :eid AND nombre LIKE '%Villa Francis%' LIMIT 1");
$stmtProy->execute([':eid' => $eid]);
$proyRow = $stmtProy->fetch(PDO::FETCH_ASSOC);
$proyId = $proyRow ? (int)$proyRow['id'] : null;
$proyNombre = $proyRow ? $proyRow['nombre'] : 'NO ENCONTRADO';

// Cuentas clave de la referencia
$cuentasRef = [
    '11020104' => 'Bco Occidente → REF: 17,830',
    '11020106' => 'BCFLOZA → REF: 126,163',
    '11020109' => 'Bco Atlantida (no debería aparecer)',
    '11050101' => 'CxC Clientes → REF: 1,147,234',
    '11050130' => 'Deudores Varios → REF: 300,000',
    '11400194' => 'Inversiones → REF: 5,000',
    '11500163' => 'Sistema Contable → REF: 5,200',
    '11600102' => 'Depositos a la Vida → REF: 5,000',
    '11600127' => 'CxC Socios → REF: 68,000',
    '12010106' => 'TERRENOS → REF: 34,204,333',
    '21010110' => 'Acreedores Varios → REF: -3,739,742',
    '27010101' => 'Prestamos L/Plazo → REF: -31,085,333',
    '36100101' => 'Perdida del Periodo → REF: 257,445',
];

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>
<style>
body{font-family:monospace;font-size:11px;padding:20px;background:#f9f9f9}
table{border-collapse:collapse;width:100%;margin-bottom:20px}
th,td{border:1px solid #ccc;padding:3px 8px;white-space:nowrap}
th{background:#1e293b;color:#fff}
.neg{background:#fecaca} .ok{background:#d1fae5} .warn{background:#fef3c7} .miss{background:#e9d5ff}
h2{margin-top:25px;border-bottom:2px solid #1e293b;padding-bottom:4px}
.ref{color:#0369a1;font-weight:bold}
</style></head><body>
<h1>🔍 Diagnóstico Balance 2023 (c.fecha) – Empresa $eid</h1>
<p>Proyecto Villa Francis detectado: <b>ID=$proyId – $proyNombre</b></p>";

// ════════════════════════════════
// 1. Saldo por cuenta (solo c.fecha, con y sin proyecto)
// ════════════════════════════════
echo "<h2>1. Saldo por cuenta usando SOLO c.fecha (sin COALESCE)</h2>";
echo "<table><tr><th>Código</th><th>Nombre BD</th><th>Referencia</th>
      <th>Saldo SIN proyecto</th><th>Saldo CON proyecto ($proyNombre)</th><th>#Comp OK</th></tr>";

foreach ($cuentasRef as $cod => $refDesc) {
    // Sin proyecto
    $s1 = $db->prepare("
        SELECT p.nombre,
               COALESCE(SUM(a.debito - a.credito), 0) AS saldo,
               COUNT(DISTINCT c.id) AS cnt_comp
        FROM puc_cuentas p
        JOIN asientos a  ON a.cuenta_id = p.id AND a.empresa_id = p.empresa_id
        JOIN comprobantes c ON c.id = a.comprobante_id AND c.empresa_id = p.empresa_id
             AND c.estado = 'registrado' AND YEAR(c.fecha) <= :year
        WHERE p.empresa_id = :eid AND p.codigo = :cod
    ");
    $s1->execute([':eid'=>$eid, ':cod'=>$cod, ':year'=>$year]);
    $r1 = $s1->fetch(PDO::FETCH_ASSOC);

    // Con proyecto
    $saldo_proy = null;
    if ($proyId) {
        $s2 = $db->prepare("
            SELECT COALESCE(SUM(a.debito - a.credito), 0) AS saldo
            FROM puc_cuentas p
            JOIN asientos a  ON a.cuenta_id = p.id AND a.empresa_id = p.empresa_id
                             AND a.proyecto_id = :proy
            JOIN comprobantes c ON c.id = a.comprobante_id AND c.empresa_id = p.empresa_id
                 AND c.estado = 'registrado' AND YEAR(c.fecha) <= :year
            WHERE p.empresa_id = :eid AND p.codigo = :cod
        ");
        $s2->execute([':eid'=>$eid, ':cod'=>$cod, ':year'=>$year, ':proy'=>$proyId]);
        $r2 = $s2->fetch(PDO::FETCH_ASSOC);
        $saldo_proy = $r2 ? (float)$r2['saldo'] : 0.0;
    }

    if (!$r1 || $r1['nombre'] === null) {
        $nombre = '<em style="color:purple">NO EXISTE EN PUC</em>';
        echo "<tr class='miss'><td>$cod</td><td>$nombre</td><td class='ref'>$refDesc</td>
              <td>—</td><td>—</td><td>0</td></tr>";
        continue;
    }

    $saldo1 = (float)$r1['saldo'];
    $nombre = htmlspecialchars((string)($r1['nombre'] ?? ''));
    $cnt    = (int)$r1['cnt_comp'];
    $bad    = $saldo1 < 0 && str_starts_with($cod, '1');
    $cls    = $bad ? 'neg' : ($cnt == 0 ? 'miss' : '');

    $col_proy = $proyId !== null ? number_format((float)$saldo_proy, 2) : '—';
    echo "<tr class='$cls'>
        <td>$cod</td><td>$nombre</td><td class='ref'>$refDesc</td>
        <td align='right'><b>".number_format($saldo1,2)."</b></td>
        <td align='right'><b>$col_proy</b></td>
        <td>$cnt</td>
    </tr>";
}
echo "</table>";

// ════════════════════════════════
// 2. Detalle asientos de cuentas problemáticas (solo c.fecha, por proyecto)
// ════════════════════════════════
$cuentasProb = ['11020104','11020106','11020109','11050130','12010106'];
echo "<h2>2. Detalle asientos de cuentas problemáticas (c.fecha <= $year, con proyecto $proyId)</h2>";

foreach ($cuentasProb as $cod) {
    $stmt = $db->prepare("
        SELECT c.id as comp_id, DATE(c.fecha) as c_fecha, c.numero,
               a.fecha as a_fecha, a.debito, a.credito, (a.debito - a.credito) as neto,
               a.proyecto_id, LEFT(c.observaciones,35) as obs
        FROM asientos a
        JOIN comprobantes c ON c.id = a.comprobante_id AND c.empresa_id = :eid
             AND c.estado = 'registrado' AND YEAR(c.fecha) <= :year
        JOIN puc_cuentas p ON p.id = a.cuenta_id AND p.codigo = :cod
        WHERE a.empresa_id = :eid2
          AND (:proy IS NULL OR a.proyecto_id = :proy2)
        ORDER BY c.fecha, c.id
        LIMIT 30
    ");
    $params = [':eid'=>$eid,':cod'=>$cod,':year'=>$year,':eid2'=>$eid,
               ':proy'=>$proyId,':proy2'=>$proyId];
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // También contar sin proyecto
    $stCnt = $db->prepare("
        SELECT COUNT(*) as total, SUM(a.debito-a.credito) as neto_total
        FROM asientos a
        JOIN comprobantes c ON c.id=a.comprobante_id AND c.empresa_id=:eid
             AND c.estado='registrado' AND YEAR(c.fecha)<=:year
        JOIN puc_cuentas p ON p.id=a.cuenta_id AND p.codigo=:cod
        WHERE a.empresa_id=:eid2
    ");
    $stCnt->execute([':eid'=>$eid,':cod'=>$cod,':year'=>$year,':eid2'=>$eid]);
    $cnt_row = $stCnt->fetch(PDO::FETCH_ASSOC);

    echo "<h3>Cuenta $cod – Total asientos (sin filtro proy): {$cnt_row['total']} | Neto total: ".number_format((float)$cnt_row['neto_total'],2)."</h3>";

    if (empty($rows)) {
        echo "<p style='color:orange'>⚠️ Sin asientos para esta cuenta con proyecto $proyId en/antes de $year</p>";
        continue;
    }
    echo "<table><tr><th>Comp</th><th>c.fecha</th><th>a.fecha</th><th>Número</th>
          <th>Débito</th><th>Crédito</th><th>Neto</th><th>Proy</th><th>Obs</th></tr>";
    foreach ($rows as $r) {
        $neto = (float)$r['neto'];
        $cls  = $neto < 0 ? 'neg' : '';
        $afecha = $r['a_fecha'] ?? '<em>NULL</em>';
        echo "<tr class='$cls'>
            <td>{$r['comp_id']}</td><td>{$r['c_fecha']}</td><td>$afecha</td>
            <td>{$r['numero']}</td>
            <td align='right'>".number_format((float)$r['debito'],2)."</td>
            <td align='right'>".number_format((float)$r['credito'],2)."</td>
            <td align='right' ".($neto<0?"style='color:red'":"")."><b>".number_format($neto,2)."</b></td>
            <td>{$r['proyecto_id']}</td>
            <td>".htmlspecialchars((string)($r['obs'] ?? ''))."</td>
        </tr>";
    }
    echo "</table>";
}

// ════════════════════════════════
// 3. Distribución de comprobantes por año y proyecto
// ════════════════════════════════
echo "<h2>3. Comprobantes por año de c.fecha (estado=registrado)</h2>";
$stmt4 = $db->query("
    SELECT YEAR(c.fecha) as anio, a.proyecto_id, COUNT(DISTINCT c.id) as comp_cnt,
           COUNT(a.id) as total_asientos
    FROM comprobantes c
    JOIN asientos a ON a.comprobante_id = c.id AND a.empresa_id = c.empresa_id
    WHERE c.empresa_id = $eid AND c.estado = 'registrado'
    GROUP BY YEAR(c.fecha), a.proyecto_id
    ORDER BY anio DESC, comp_cnt DESC
    LIMIT 30
");
$rows4 = $stmt4->fetchAll(PDO::FETCH_ASSOC);
echo "<table><tr><th>Año (c.fecha)</th><th>Proyecto ID</th><th>#Comprobantes</th><th>#Asientos</th></tr>";
foreach ($rows4 as $r) {
    $hilight = ($r['anio'] == 2023) ? 'ok' : '';
    echo "<tr class='$hilight'><td>{$r['anio']}</td><td>".($r['proyecto_id'] ?? 'NULL')."</td>
          <td>{$r['comp_cnt']}</td><td>{$r['total_asientos']}</td></tr>";
}
echo "</table>";

echo "</body></html>";
