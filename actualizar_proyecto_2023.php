<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Database;

$db  = Database::getInstance()->getPdo();
$eid  = 1;
$year = 2023;

// Detectar proyecto Villa Francis
$stmtProy = $db->prepare("SELECT id, nombre FROM proyectos WHERE empresa_id = :eid AND nombre LIKE '%Villa Francis%' LIMIT 1");
$stmtProy->execute([':eid' => $eid]);
$proyRow = $stmtProy->fetch(PDO::FETCH_ASSOC);
$proyId   = $proyRow ? (int)$proyRow['id'] : null;
$proyNombre = $proyRow ? $proyRow['nombre'] : 'NO ENCONTRADO';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>
<style>
body{font-family:monospace;font-size:13px;padding:30px;background:#f9f9f9;max-width:1000px;margin:auto}
table{border-collapse:collapse;width:100%;margin:20px 0}
th,td{border:1px solid #ccc;padding:5px 10px}
th{background:#1e293b;color:#fff}
.ok{background:#d1fae5} .warn{background:#fef3c7} .neg{background:#fecaca}
.btn{display:inline-block;padding:12px 28px;background:#dc2626;color:white;font-weight:bold;
     text-decoration:none;border:none;border-radius:8px;cursor:pointer;font-size:14px;margin:10px 5px}
.btn-ok{background:#059669}
h2{margin-top:30px;border-bottom:2px solid #1e293b;padding-bottom:5px}
.box{background:white;border:1px solid #e2e8f0;border-radius:8px;padding:20px;margin:15px 0;box-shadow:0 1px 4px rgba(0,0,0,.08)}
</style></head><body>
<h1>🛠 Asignación Proyecto Villa Francis – Asientos 2023</h1>
<div class='box'>
<b>Proyecto detectado:</b> ID=$proyId – $proyNombre<br>
<b>Empresa:</b> $eid | <b>Año:</b> $year
</div>";

if (!$proyId) {
    echo "<p style='color:red'>❌ No se encontró el proyecto Villa Francis. Verifica la base de datos.</p></body></html>";
    exit;
}

// ── PASO 1: Contar asientos sin proyecto_id en comprobantes de 2023
$stmtCount = $db->prepare("
    SELECT COUNT(a.id) as total,
           SUM(a.debito) as tot_deb,
           SUM(a.credito) as tot_crd,
           COUNT(DISTINCT c.id) as comp_cnt
    FROM asientos a
    JOIN comprobantes c ON c.id = a.comprobante_id
         AND c.empresa_id = :eid
         AND c.estado = 'registrado'
         AND YEAR(c.fecha) = :year
    WHERE a.empresa_id = :eid2
      AND (a.proyecto_id IS NULL OR a.proyecto_id != :proy)
");
$stmtCount->execute([':eid'=>$eid,':year'=>$year,':eid2'=>$eid,':proy'=>$proyId]);
$cnt = $stmtCount->fetch(PDO::FETCH_ASSOC);

echo "<h2>1. Asientos de $year sin proyecto Villa Francis (ID=$proyId)</h2>";
echo "<div class='box warn'>
<b>Asientos huérfanos:</b> {$cnt['total']}<br>
<b>Comprobantes afectados:</b> {$cnt['comp_cnt']}<br>
<b>Total débito:</b> ".number_format((float)$cnt['tot_deb'],2)."<br>
<b>Total crédito:</b> ".number_format((float)$cnt['tot_crd'],2)."
</div>";

// ── PASO 2: Ver muestra de esos asientos
$stmtSample = $db->prepare("
    SELECT c.id as comp_id, DATE(c.fecha) as fecha, c.numero,
           p.codigo, p.nombre as cuenta_nom,
           a.debito, a.credito, a.proyecto_id,
           LEFT(c.observaciones,40) as obs
    FROM asientos a
    JOIN comprobantes c ON c.id = a.comprobante_id
         AND c.empresa_id = :eid AND c.estado = 'registrado' AND YEAR(c.fecha) = :year
    JOIN puc_cuentas p ON p.id = a.cuenta_id
    WHERE a.empresa_id = :eid2
      AND (a.proyecto_id IS NULL OR a.proyecto_id != :proy)
    ORDER BY c.fecha, c.id
    LIMIT 30
");
$stmtSample->execute([':eid'=>$eid,':year'=>$year,':eid2'=>$eid,':proy'=>$proyId]);
$sample = $stmtSample->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>2. Muestra de asientos sin proyecto (primeros 30)</h2>";
echo "<table><tr><th>Comp</th><th>Fecha</th><th>Número</th><th>Código</th><th>Cuenta</th>
      <th>Débito</th><th>Crédito</th><th>Proy actual</th><th>Observación</th></tr>";
foreach ($sample as $r) {
    $pActual = $r['proyecto_id'] ?? '<b style="color:red">NULL</b>';
    echo "<tr>
        <td>{$r['comp_id']}</td><td>{$r['fecha']}</td><td>{$r['numero']}</td>
        <td>{$r['codigo']}</td><td>".htmlspecialchars((string)$r['cuenta_nom'])."</td>
        <td align='right'>".number_format((float)$r['debito'],2)."</td>
        <td align='right'>".number_format((float)$r['credito'],2)."</td>
        <td>$pActual</td>
        <td>".htmlspecialchars((string)($r['obs'] ?? ''))."</td>
    </tr>";
}
echo "</table>";

// ── PASO 3: Acción – actualizar o simular
$accion = $_GET['accion'] ?? '';

if ($accion === 'actualizar') {
    try {
        $db->beginTransaction();
        $stmtUpdate = $db->prepare("
            UPDATE asientos a
            JOIN comprobantes c ON c.id = a.comprobante_id
                 AND c.empresa_id = :eid
                 AND c.estado = 'registrado'
                 AND YEAR(c.fecha) = :year
            SET a.proyecto_id = :proy
            WHERE a.empresa_id = :eid2
              AND (a.proyecto_id IS NULL OR a.proyecto_id != :proy2)
        ");
        $stmtUpdate->execute([':eid'=>$eid,':year'=>$year,':proy'=>$proyId,':eid2'=>$eid,':proy2'=>$proyId]);
        $affected = $stmtUpdate->rowCount();
        $db->commit();
        echo "<div class='box ok' style='font-size:15px'>
            ✅ <b>¡Actualización exitosa!</b><br>
            <b>$affected asientos</b> fueron asignados al proyecto \"$proyNombre\" (ID=$proyId).<br>
            <a href='actualizar_proyecto_2023.php'>Recargar para verificar</a>
        </div>";
    } catch (\Throwable $e) {
        $db->rollBack();
        echo "<div class='box neg'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
} else {
    echo "<h2>3. Acción</h2>
    <div class='box'>
    <p>¿Asignar los <b>{$cnt['total']} asientos</b> de $year al proyecto \"$proyNombre\" (ID=$proyId)?</p>
    <p style='color:#64748b;font-size:12px'>⚠️ Esto actualizará el campo <code>proyecto_id</code> de todos los asientos de comprobantes registrados del año $year que actualmente no estén asignados a ese proyecto.</p>
    <a class='btn btn-ok' href='?accion=actualizar'>✅ SÍ – Asignar proyecto a los $year asientos</a>
    <a class='btn' href='libros_oficiales.php'>❌ Cancelar</a>
    </div>";
}

echo "</body></html>";
