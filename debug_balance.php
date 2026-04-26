<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Database;

$db  = Database::getInstance()->getPdo();
$eid  = 1;
$year = 2023;

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>
<style>body{font-family:sans-serif;font-size:12px;padding:20px}
table{border-collapse:collapse;width:100%} th,td{border:1px solid #ccc;padding:3px 7px}
th{background:#ddd} .neg{background:#fecaca} .ok{background:#d1fae5}
.warn{background:#fef3c7} h2{margin-top:30px;border-bottom:2px solid #333}
</style></head><body>
<h1>🔍 Diagnóstico Balance General – Empresa $eid, Año $year</h1>";

// ── 1. SALDO POR CUENTA SIN FILTRO DE PROYECTO ───────────────────────────
echo "<h2>1. Saldo de cuentas APR (sin filtro de proyecto, COALESCE)</h2>";
echo "<p>Fuente de verdad: todos los asientos con comprobante registrado y COALESCE(a.fecha, c.fecha) <= $year</p>";
$sql = "SELECT p.codigo, p.nombre, p.tipo_cuenta, p.naturaleza,
    COALESCE(SUM(CASE WHEN c.id IS NOT NULL THEN (a.debito - a.credito) ELSE 0 END), 0) AS saldo_neto,
    COUNT(CASE WHEN c.id IS NOT NULL THEN 1 END) AS cnt_ok,
    COUNT(CASE WHEN c.id IS NULL THEN 1 END) AS cnt_hf
  FROM puc_cuentas p
  LEFT JOIN asientos a ON a.cuenta_id = p.id AND a.empresa_id = p.empresa_id
  LEFT JOIN comprobantes c ON c.id = a.comprobante_id AND c.empresa_id = p.empresa_id
      AND c.estado = 'registrado' AND YEAR(COALESCE(a.fecha, c.fecha)) <= $year
  WHERE p.empresa_id = $eid AND p.tipo_cuenta IN ('A','P','R') AND p.activa = 1
  GROUP BY p.id, p.codigo
  HAVING ABS(saldo_neto) > 0.001
  ORDER BY p.codigo";
$rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
echo "<table><tr><th>Código</th><th>Nombre</th><th>Tipo</th><th>Nat</th>
    <th>Saldo neto (sin proyecto)</th><th>#OK</th><th>#Huérf</th></tr>";
$ta = $tp = $tr = 0;
foreach ($rows as $r) {
    $s = (float)$r['saldo_neto'];
    if ($r['tipo_cuenta']==='A') $ta += $s;
    if ($r['tipo_cuenta']==='P') $tp += $s;
    if ($r['tipo_cuenta']==='R') $tr += $s;
    $bad = ($r['tipo_cuenta']==='A' && $s<0) || ($r['tipo_cuenta']==='P' && $s>0);
    $cls = $bad ? 'neg' : '';
    echo "<tr class='$cls'><td>{$r['codigo']}</td><td>{$r['nombre']}</td>
        <td>{$r['tipo_cuenta']}</td><td>{$r['naturaleza']}</td>
        <td align='right'>".number_format($s,2)."</td>
        <td>{$r['cnt_ok']}</td><td>{$r['cnt_hf']}</td></tr>";
}
echo "</table><p>TOTAL A: <b>".number_format($ta,2)."</b> | TOTAL P: <b>".number_format($tp,2)."</b> | TOTAL R: <b>".number_format($tr,2)."</b></p>";

// ── 2. QUÉ PROYECTOS EXISTEN EN ASIENTOS de 2023 ─────────────────────────
echo "<h2>2. Proyectos en asientos 2023</h2>";
$sql2 = "SELECT pry.id, pry.nombre, COUNT(a.id) as cnt, SUM(a.debito) as deb, SUM(a.credito) as crd
  FROM asientos a
  JOIN comprobantes c ON c.id=a.comprobante_id AND c.empresa_id=$eid AND c.estado='registrado'
       AND YEAR(COALESCE(a.fecha, c.fecha)) <= $year
  LEFT JOIN proyectos pry ON pry.id = a.proyecto_id
  WHERE a.empresa_id = $eid
  GROUP BY a.proyecto_id ORDER BY cnt DESC LIMIT 20";
$rows2 = $db->query($sql2)->fetchAll(PDO::FETCH_ASSOC);
echo "<table><tr><th>Proy ID</th><th>Nombre proyecto</th><th>#Asientos</th><th>Total débito</th><th>Total crédito</th></tr>";
foreach ($rows2 as $r2) {
    $pid = $r2['id'] ?? '(NULL - sin proyecto)';
    $pnm = $r2['nombre'] ?? '<em>Sin proyecto</em>';
    echo "<tr><td>$pid</td><td>$pnm</td><td>{$r2['cnt']}</td>
        <td align='right'>".number_format((float)$r2['deb'],2)."</td>
        <td align='right'>".number_format((float)$r2['crd'],2)."</td></tr>";
}
echo "</table>";

// ── 3. CUENTAS IMPORTANTES: saldo con proy vs sin proy ───────────────────
echo "<h2>3. Diferencia con/sin filtro de proyecto – cuentas clave</h2>";
$cuentasClave = ['12010106','11020104','11020106','11020108','27010101','36100101','11050101','11050130'];
echo "<table><tr><th>Código</th><th>Nombre</th>
    <th>Saldo SIN proyecto</th><th>Saldo CON proyecto principal</th><th>Diferencia</th></tr>";

// Buscar el proyecto_id más común en asientos con comprobantes válidos
$stTopProy = $db->query("SELECT a.proyecto_id, COUNT(*) cnt
  FROM asientos a JOIN comprobantes c ON c.id=a.comprobante_id AND c.empresa_id=$eid
       AND c.estado='registrado' AND YEAR(COALESCE(a.fecha,c.fecha))<=$year
  WHERE a.empresa_id=$eid AND a.proyecto_id IS NOT NULL
  GROUP BY a.proyecto_id ORDER BY cnt DESC LIMIT 1");
$topProy = $stTopProy->fetch();
$topProyId = $topProy ? (int)$topProy['proyecto_id'] : 0;

foreach ($cuentasClave as $cod) {
    $stmt = $db->prepare("SELECT p.nombre,
        COALESCE(SUM(CASE WHEN c.id IS NOT NULL THEN (a.debito-a.credito) ELSE 0 END), 0) as sin_proy,
        COALESCE(SUM(CASE WHEN c.id IS NOT NULL AND a.proyecto_id = :pid THEN (a.debito-a.credito) ELSE 0 END), 0) as con_proy
      FROM puc_cuentas p
      LEFT JOIN asientos a ON a.cuenta_id=p.id AND a.empresa_id=p.empresa_id
      LEFT JOIN comprobantes c ON c.id=a.comprobante_id AND c.empresa_id=p.empresa_id
          AND c.estado='registrado' AND YEAR(COALESCE(a.fecha,c.fecha))<=$year
      WHERE p.empresa_id=$eid AND p.codigo = :cod");
    $stmt->execute([':cod'=>$cod, ':pid'=>$topProyId]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$r) continue;
    $dif = (float)$r['sin_proy'] - (float)$r['con_proy'];
    $cls = abs($dif)>0.01 ? 'warn' : '';
    echo "<tr class='$cls'><td>$cod</td><td>{$r['nombre']}</td>
        <td align='right'>".number_format((float)$r['sin_proy'],2)."</td>
        <td align='right'>".number_format((float)$r['con_proy'],2)."</td>
        <td align='right' style='color:red'>".number_format($dif,2)."</td></tr>";
}
echo "</table><p><em>Proyecto principal detectado: ID=$topProyId</em></p>";

// ── 4. DETALLE ASIENTOS NEGATIVOS DE ACTIVOS ─────────────────────────────
echo "<h2>4. Asientos de comp_id=9999 por cuenta (primer batch – ¿tiene proyecto_id?)</h2>";
$sqlc = "SELECT p.codigo, p.nombre, a.proyecto_id,
    SUM(a.debito) as deb, SUM(a.credito) as crd, COUNT(*) cnt
  FROM asientos a
  JOIN puc_cuentas p ON p.id=a.cuenta_id AND p.empresa_id=a.empresa_id
  WHERE a.comprobante_id=9999 AND a.empresa_id=$eid AND p.tipo_cuenta IN('A','P','R')
  GROUP BY p.codigo, a.proyecto_id ORDER BY p.codigo";
$rowsc = $db->query($sqlc)->fetchAll(PDO::FETCH_ASSOC);
echo "<table><tr><th>Código</th><th>Nombre</th><th>proyecto_id</th><th>Débito</th><th>Crédito</th><th>Neto</th><th>#</th></tr>";
foreach ($rowsc as $rc) {
    $neto = (float)$rc['deb'] - (float)$rc['crd'];
    $pid = $rc['proyecto_id'] ?? '<b style=color:red>NULL</b>';
    echo "<tr><td>{$rc['codigo']}</td><td>{$rc['nombre']}</td>
        <td>$pid</td>
        <td align='right'>".number_format((float)$rc['deb'],2)."</td>
        <td align='right'>".number_format((float)$rc['crd'],2)."</td>
        <td align='right'>".number_format($neto,2)."</td>
        <td>{$rc['cnt']}</td></tr>";
}
echo "</table>";

echo "</body></html>";
