<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Database;

$db  = Database::getInstance()->getPdo();
$eid = 1;

// ══════════════════════════════════════════════════════════
// VALORES DE REFERENCIA (documento físico Villa Francis 2023)
// ══════════════════════════════════════════════════════════
// Formato: 'codigo' => ['debito' => X, 'credito' => Y, 'nota' => '...']
// Activos  → debito = saldo, credito = 0
// Pasivos  → debito = 0, credito = saldo
// Perdida  → debito = saldo (reduce patrimonio), credito = 0
// ── NOTA: 11050101 CxC Clientes = 1,147,234 ya esta OK en el proyecto. NO tocar.
$saldosRef = [
    // ACTIVOS CIRCULANTE
    '11020104' => ['debito'=>17830,      'credito'=>0,         'nota'=>'Bco Occidente 21-434-024809'],
    '11020106' => ['debito'=>126163,     'credito'=>0,         'nota'=>'BCFLOZA Ocid 21-701-0558'],
    '11050130' => ['debito'=>300000,     'credito'=>0,         'nota'=>'Deudores Varios'],
    '11400194' => ['debito'=>5000,       'credito'=>0,         'nota'=>'Inversiones'],
    '11500163' => ['debito'=>5200,       'credito'=>0,         'nota'=>'Sistema Contable'],
    '11600102' => ['debito'=>5000,       'credito'=>0,         'nota'=>'Depositos a la Vida'],
    '11600127' => ['debito'=>68000,      'credito'=>0,         'nota'=>'Cuentas por Cobrar Socios'],
    // ACTIVOS FIJO
    '12010106' => ['debito'=>34204333,   'credito'=>0,         'nota'=>'Terrenos'],
    // PASIVOS CIRCULANTE
    '21010110' => ['debito'=>0,          'credito'=>3739742,   'nota'=>'Acreedores Varios'],
    // PASIVOS FIJO
    '27010101' => ['debito'=>0,          'credito'=>31085333,  'nota'=>'Prestamos L/Plazo'],
    // PATRIMONIO – PERDIDA DEL PERIODO (debito porque reduce patrimonio)
    '36100101' => ['debito'=>257445,     'credito'=>0,         'nota'=>'Perdida del Periodo'],
];

// ── Detectar proyecto Villa Francis
$stmtProy = $db->prepare("SELECT id, nombre FROM proyectos WHERE empresa_id=:eid AND nombre LIKE '%Villa Francis%' LIMIT 1");
$stmtProy->execute([':eid'=>$eid]);
$proyRow  = $stmtProy->fetch(PDO::FETCH_ASSOC);
$proyId   = $proyRow ? (int)$proyRow['id']    : null;
$proyNom  = $proyRow ? $proyRow['nombre']      : 'NO ENCONTRADO';

// ── Buscar periodo diciembre 2023
$stmtPer = $db->prepare("SELECT id FROM periodos WHERE empresa_id=:eid AND anio=2023 AND mes=12 LIMIT 1");
$stmtPer->execute([':eid'=>$eid]);
$periodoRow = $stmtPer->fetch(PDO::FETCH_ASSOC);
$periodoId  = $periodoRow ? (int)$periodoRow['id'] : null;

// ── Buscar tipo_comprobante disponible
$stmtTipo = $db->prepare("SELECT id, codigo, nombre FROM tipos_comprobante WHERE empresa_id=:eid LIMIT 5");
$stmtTipo->execute([':eid'=>$eid]);
$tiposComp = $stmtTipo->fetchAll(PDO::FETCH_ASSOC);
$tipoId    = !empty($tiposComp) ? (int)$tiposComp[0]['id'] : null;

// ── Resolver IDs de cuentas desde puc
$codigosList = implode(',', array_map(fn($c) => $db->quote((string)$c), array_keys($saldosRef)));
$stmtCtas = $db->query("SELECT id, codigo, nombre, naturaleza, tipo_cuenta FROM puc_cuentas 
                         WHERE empresa_id=$eid AND codigo IN ($codigosList)");
$cuentaMap = [];  // codigo => [id, nombre, naturaleza, tipo_cuenta]
foreach ($stmtCtas->fetchAll(PDO::FETCH_ASSOC) as $c) {
    $cuentaMap[$c['codigo']] = $c;
}

// ── Calcular totales de referencia
$totalDeb = array_sum(array_column($saldosRef, 'debito'));
$totalCrd = array_sum(array_column($saldosRef, 'credito'));
$diferencia = abs($totalDeb - $totalCrd);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>
<style>
body{font-family:'Segoe UI',sans-serif;font-size:13px;padding:30px;background:#f1f5f9;max-width:1100px;margin:auto}
table{border-collapse:collapse;width:100%;margin:15px 0;background:white;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.08)}
th,td{border:1px solid #e2e8f0;padding:6px 12px}
th{background:#1e293b;color:#fff;font-weight:600}
.ok{background:#d1fae5} .warn{background:#fef3c7} .neg{background:#fecaca} .miss{background:#ede9fe}
.btn{display:inline-block;padding:12px 32px;background:#059669;color:white;font-weight:bold;
     text-decoration:none;border:none;border-radius:8px;cursor:pointer;font-size:14px;margin:8px 4px}
.btn-del{background:#dc2626}
.box{background:white;border:1px solid #e2e8f0;border-radius:10px;padding:20px;margin:15px 0;box-shadow:0 1px 4px rgba(0,0,0,.06)}
h1{color:#1e293b} h2{color:#1e293b;margin-top:25px;border-bottom:2px solid #1e293b;padding-bottom:5px}
.badge{display:inline-block;padding:2px 8px;border-radius:9999px;font-size:11px;font-weight:600}
.badge-ok{background:#d1fae5;color:#065f46}
.badge-err{background:#fecaca;color:#991b1b}
</style></head><body>
<h1>🧹 Reimportar Saldos Históricos 2023 – Villa Francis</h1>
<div class='box'>
<b>Proyecto:</b> $proyNom (ID=$proyId) | 
<b>Empresa:</b> $eid | 
<b>Periodo dic-2023:</b> ID=$periodoId |
<b>Tipo comp:</b> ID=$tipoId (".($tiposComp[0]['codigo'] ?? 'N/A')." – ".($tiposComp[0]['nombre'] ?? 'N/A').")
</div>";

// ══════════════════════════════════════════════════════════
// PASO 1: Estado de comp 9999 a eliminar
// ══════════════════════════════════════════════════════════
$stmtC9 = $db->query("
    SELECT COUNT(a.id) as cnt, SUM(a.debito) as deb, SUM(a.credito) as crd
    FROM asientos a WHERE a.comprobante_id=9999 AND a.empresa_id=$eid
");
$c9 = $stmtC9->fetch(PDO::FETCH_ASSOC);

echo "<h2>1. Comprobante 9999 que será eliminado</h2>
<div class='box neg'>
⚠️ <b>{$c9['cnt']} asientos</b> serán eliminados | 
Total débito: ".number_format((float)$c9['deb'],2)." | 
Total crédito: ".number_format((float)$c9['crd'],2)."
</div>";

// ══════════════════════════════════════════════════════════
// PASO 2: Preview de nuevos asientos
// ══════════════════════════════════════════════════════════
echo "<h2>2. Nuevos asientos a crear (valores del documento de referencia)</h2>";
echo "<table><tr><th>Código</th><th>Nombre BD</th><th>Nota Referencia</th><th>Débito</th><th>Crédito</th><th>Estado cuenta</th></tr>";

$errores = [];
foreach ($saldosRef as $cod => $vals) {
    $cuenta = $cuentaMap[$cod] ?? null;
    $debFmt = $vals['debito']  > 0 ? number_format($vals['debito'],  2) : '—';
    $crdFmt = $vals['credito'] > 0 ? number_format($vals['credito'], 2) : '—';
    if (!$cuenta) {
        $errores[] = $cod;
        $cls   = 'miss';
        $badge = "<span class='badge badge-err'>NO EXISTE EN PUC</span>";
    } else {
        $cls   = '';
        $badge = "<span class='badge badge-ok'>✓ {$cuenta['nombre']}</span>";
    }
    echo "<tr class='$cls'>
        <td><b>$cod</b></td>
        <td>".($cuenta ? htmlspecialchars($cuenta['nombre']) : '<em>—</em>')."</td>
        <td>".htmlspecialchars($vals['nota'])."</td>
        <td align='right'>$debFmt</td>
        <td align='right'>$crdFmt</td>
        <td>$badge</td>
    </tr>";
}
$sumD = '<tr style="font-weight:bold;background:#f1f5f9"><td colspan="3">TOTALES</td>'
      . '<td align="right">'.number_format($totalDeb,2).'</td>'
      . '<td align="right">'.number_format($totalCrd,2).'</td><td></td></tr>';
echo $sumD . "</table>";

if ($diferencia > 0) {
    echo "<div class='box warn'>
    ⚠️ <b>Diferencia débito vs crédito: ".number_format($diferencia,2)."</b><br>
    Esto es la diferencia histórica conocida del documento original (el balance de Villa Francis 2023 no cuadraba perfectamente). 
    Se creará una línea adicional para cuadrar el asiento contable usando la cuenta de Patrimonio (36100101) o una cuenta puente.
    </div>";
}

if (!empty($errores)) {
    echo "<div class='box miss'>
    ⚠️ Las siguientes cuentas <b>NO EXISTEN en puc_cuentas</b> y serán omitidas:<br>
    <b>".implode(', ', $errores)."</b><br>
    Puedes crearlas en el catálogo de cuentas primero, o el script las omitirá.
    </div>";
}

if (!$proyId || !$tipoId) {
    echo "<div class='box neg'>❌ Faltan datos: proyId=$proyId, tipoId=$tipoId. Verifica configuración.</div>
    </body></html>";
    exit;
}

// ══════════════════════════════════════════════════════════
// PASO 3: Ejecución
// ══════════════════════════════════════════════════════════
$accion = $_GET['accion'] ?? '';

if ($accion === 'ejecutar') {
    try {
        $db->beginTransaction();

        // 3a. Eliminar asientos y comprobante 9999
        $db->exec("DELETE FROM asientos WHERE comprobante_id=9999 AND empresa_id=$eid");
        $db->exec("DELETE FROM comprobantes WHERE id=9999 AND empresa_id=$eid");

        // 3b. Crear o buscar periodo dic-2023
        if (!$periodoId) {
            $db->prepare("INSERT INTO periodos (empresa_id,anio,mes,estado) VALUES (?,2023,12,'cerrado')")->execute([$eid]);
            $periodoId = (int)$db->lastInsertId();
        }

        // 3c. Crear nuevo comprobante de apertura histórica
        $stmtNComp = $db->prepare("
            INSERT INTO comprobantes (empresa_id, periodo_id, tipo_comp_id, fecha, numero, observaciones, estado)
            VALUES (:eid, :pid, :tid, '2023-12-31', 'APE-2023', 'Saldos de apertura histórica 2023 – Villa Francis', 'registrado')
        ");
        $stmtNComp->execute([':eid'=>$eid, ':pid'=>$periodoId, ':tid'=>$tipoId]);
        $newCompId = (int)$db->lastInsertId();

        // 3d. Insertar asientos
        $stmtIns = $db->prepare("
            INSERT INTO asientos (empresa_id, comprobante_id, cuenta_id, debito, credito, descripcion, proyecto_id, linea)
            VALUES (:eid, :cid, :cueid, :deb, :crd, :desc, :proy, :lin)
        ");

        $linea = 1;
        $insertados = 0;
        $omitidos   = [];
        $runDeb = 0;
        $runCrd = 0;

        foreach ($saldosRef as $cod => $vals) {
            $cuenta = $cuentaMap[$cod] ?? null;
            if (!$cuenta) {
                $omitidos[] = $cod;
                continue;
            }
            $stmtIns->execute([
                ':eid'  => $eid,
                ':cid'  => $newCompId,
                ':cueid'=> (int)$cuenta['id'],
                ':deb'  => $vals['debito'],
                ':crd'  => $vals['credito'],
                ':desc' => $vals['nota'],
                ':proy' => $proyId,
                ':lin'  => $linea++,
            ]);
            $runDeb += $vals['debito'];
            $runCrd += $vals['credito'];
            $insertados++;
        }

        // 3e. Asiento de cuadre si hay diferencia
        $diff = $runDeb - $runCrd;
        if (abs($diff) > 0.01) {
            // Buscar cuenta "Diferencia de apertura" o usar la misma de perdida
            $cuentaCuadre = $cuentaMap['36100101'] ?? null;
            if ($cuentaCuadre) {
                $stmtIns->execute([
                    ':eid'  => $eid,
                    ':cid'  => $newCompId,
                    ':cueid'=> (int)$cuentaCuadre['id'],
                    ':deb'  => $diff > 0 ? 0     : abs($diff),
                    ':crd'  => $diff > 0 ? $diff  : 0,
                    ':desc' => 'Diferencia de cuadre apertura histórica 2023',
                    ':proy' => $proyId,
                    ':lin'  => $linea++,
                ]);
                $insertados++;
            }
        }

        $db->commit();
        echo "<div class='box ok' style='font-size:15px;margin-top:20px'>
        ✅ <b>¡Operación completada!</b><br>
        • Comprobante 9999 eliminado (along with its {$c9['cnt']} asientos)<br>
        • Nuevo comprobante creado: ID=$newCompId (APE-2023, fecha 2023-12-31)<br>
        • <b>$insertados asientos</b> insertados con proyecto_id=$proyId<br>
        ".(!empty($omitidos) ? "• Cuentas omitidas (no existen en PUC): <b>".implode(', ',$omitidos)."</b><br>" : "")."
        <br>
        <a href='libros_oficiales.php' style='color:#065f46;font-weight:bold'>→ Ver Balance General</a>
        </div>";

    } catch (\Throwable $e) {
        $db->rollBack();
        echo "<div class='box neg'>❌ <b>Error:</b> " . htmlspecialchars($e->getMessage()) . "</div>";
    }

} else {
    // Botón de confirmación
    echo "<h2>3. Confirmar operación</h2>
    <div class='box'>
    <p><b>¿Ejecutar los siguientes pasos?</b></p>
    <ol>
    <li>🗑 Eliminar comprobante 9999 y sus <b>{$c9['cnt']} asientos</b></li>
    <li>➕ Crear comprobante <b>APE-2023</b> fechado 2023-12-31</li>
    <li>✅ Insertar <b>".(count($saldosRef) - count($errores))." asientos</b> con los saldos correctos del documento de referencia</li>
    <li>🏷 Asignar <b>proyecto_id=$proyId</b> ($proyNom) a todos los asientos</li>
    </ol>
    <p style='color:#64748b;font-size:12px'>⚠️ Esta operación modifica la base de datos. Se ejecuta dentro de una transacción (se revierte si hay error).</p>
    <a class='btn' href='?accion=ejecutar'>✅ SÍ – Ejecutar reimportación</a>
    <a class='btn btn-del' href='libros_oficiales.php'>❌ Cancelar</a>
    </div>";
}

echo "</body></html>";
