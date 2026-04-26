<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();

$db  = Database::getInstance()->getPdo();
$TOL = 0.01;

// ── Parámetros ──
$YEAR    = (int)   ($_GET['year']   ?? 2023);
$TARGET  = (float) ($_GET['target'] ?? 1_147_234.00);
$EMP_ID  = isset($_GET['eid']) ? (int)$_GET['eid'] : Auth::empresaId();
$cidsGet = isset($_GET['cids']) ? array_map('intval', (array)$_GET['cids']) : [];

// ── Empresas ──
$empresas  = $db->query("SELECT id, nombre FROM empresas WHERE activa=1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$empNombre = '';
foreach ($empresas as $e) { if ((int)$e['id'] === $EMP_ID) { $empNombre = $e['nombre']; break; } }

// ── Cuentas Activo con saldo ──
$stAll = $db->prepare("SELECT id, codigo, nombre, naturaleza FROM puc_cuentas WHERE empresa_id=:e AND activa=1 AND tipo_cuenta='A' ORDER BY codigo");
$stAll->execute([':e' => $EMP_ID]);
$todasA = $stAll->fetchAll(PDO::FETCH_ASSOC);

// Default: cuentas con código 13x O 11x que contengan clientes/cobrar en el nombre
$defaultCids = [];
foreach ($todasA as $c) {
    if (strlen($c['codigo']) < 5) continue;
    if (preg_match('/^13/', $c['codigo']) ||
        (preg_match('/^11/', $c['codigo']) && (stripos($c['nombre'], 'clientes') !== false || stripos($c['nombre'], 'cobrar') !== false))) {
        $defaultCids[] = (int)$c['id'];
    }
}
$cuentaIds = !empty($cidsGet) ? $cidsGet : $defaultCids;

// ─────────────────────────────────────────────────────────────
// ACCIONES POST
// ─────────────────────────────────────────────────────────────
$msg = $msgErr = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'reubicar';

    // ── REUBICAR asientos ──
    if ($action === 'reubicar' && !empty($_POST['ids'])) {
        $ids = array_map('intval', (array)$_POST['ids']);
        $nf  = trim($_POST['nueva_fecha'] ?? '');

        if (empty($nf) || !strtotime($nf)) {
            $msgErr = "Fecha inválida.";
        } else {
            $db->beginTransaction();
            try {
                // Buscar o crear comprobante destino
                $consec = "MIG-$nf";
                $stC = $db->prepare("SELECT id FROM comprobantes WHERE observaciones=:c LIMIT 1");
                $stC->execute(['c' => $consec]);
                $compId = $stC->fetchColumn();

                if (!$compId) {
                    $stI = $db->prepare("SELECT c.empresa_id,c.tipo_comp_id,c.periodo_id FROM comprobantes c JOIN asientos a ON a.comprobante_id=c.id WHERE a.id=:a LIMIT 1");
                    $stI->execute(['a' => $ids[0]]);
                    $info = $stI->fetch(PDO::FETCH_ASSOC);
                    $emp  = $info['empresa_id']   ?? $EMP_ID;
                    $tip  = $info['tipo_comp_id'] ?? 1;

                    $stP = $db->prepare("SELECT id FROM periodos WHERE empresa_id=:e AND mes=MONTH(:f) AND anio=YEAR(:f2) LIMIT 1");
                    $stP->execute(['e'=>$emp,'f'=>$nf,'f2'=>$nf]);
                    $per = $stP->fetchColumn() ?: ($info['periodo_id'] ?? 1);

                    $stN = $db->prepare("SELECT COALESCE(MAX(numero),0)+1 FROM comprobantes WHERE empresa_id=:e AND tipo_comp_id=:t");
                    $stN->execute(['e'=>$emp,'t'=>$tip]);
                    $num = $stN->fetchColumn() ?: 99999;

                    $stX = $db->prepare("INSERT INTO comprobantes (empresa_id,tipo_comp_id,numero,fecha,observaciones,tercero_id,usuario_id,estado,periodo_id) VALUES(:e,:t,:n,:f,:o,NULL,1,'registrado',:p)");
                    $stX->execute(['e'=>$emp,'t'=>$tip,'n'=>$num,'f'=>$nf,'o'=>$consec,'p'=>$per]);
                    $compId = $db->lastInsertId();
                }

                $pls = implode(',', array_fill(0, count($ids), '?'));
                $db->prepare("UPDATE asientos SET comprobante_id=?, fecha=? WHERE id IN ($pls)")
                   ->execute(array_merge([$compId, $nf], $ids));

                $db->commit();
                $qs = http_build_query(['eid'=>$EMP_ID,'year'=>$YEAR,'target'=>$TARGET,'cids'=>$cuentaIds,'ok'=>count($ids)]);
                header("Location: {$_SERVER['PHP_SELF']}?$qs");
                exit;
            } catch (\Throwable $e) {
                $db->rollBack();
                $msgErr = "Error: " . $e->getMessage();
            }
        }
    }

    // ── SINCRONIZAR TODAS LAS FECHAS CON EXCEL ──
    if ($action === 'sync_all_dates') {
        $jsonPath = __DIR__ . '/database/excel_full_sync.json';
        if (!file_exists($jsonPath)) {
            $msgErr = "No se encontró database/excel_full_sync.json. Ejecuta la extracción de datos primero.";
        } else {
            $syncMap = json_decode(file_get_contents($jsonPath), true);
            if (!$syncMap) {
                $msgErr = "Error al leer el archivo JSON de mapeo.";
            } else {
                try {
                    // --- OPTIMIZACIÓN: Crear índice para que sea instantáneo ---
                    try {
                        $db->exec("CREATE INDEX idx_asientos_conteo ON asientos(conteo)");
                    } catch (\Exception $eIndex) {
                        // Ignorar si el índice ya existe
                    }

                    $stUpd = $db->prepare("UPDATE asientos SET fecha = ? WHERE conteo = ? AND empresa_id = ?");
                    $counts = 0;
                    $existentes = $db->prepare("SELECT conteo FROM asientos WHERE empresa_id = ? AND conteo IS NOT NULL");
                    $existentes->execute([$EMP_ID]);
                    $listaConteos = $existentes->fetchAll(PDO::FETCH_COLUMN);
                    
                    $batchSize = 250; 
                    $currentBatch = 0;

                    foreach ($listaConteos as $ct) {
                        if (isset($syncMap[$ct])) {
                            if ($currentBatch === 0) {
                                $db->beginTransaction();
                            }

                            $stUpd->execute([$syncMap[$ct], $ct, $EMP_ID]);
                            if ($stUpd->rowCount() > 0) $counts++;
                            
                            $currentBatch++;

                            if ($currentBatch >= $batchSize) {
                                $db->commit();
                                $currentBatch = 0;
                            }
                        }
                    }

                    if ($currentBatch > 0 && $db->inTransaction()) {
                        $db->commit();
                    }
                    
                    header("Location: {$_SERVER['PHP_SELF']}?eid=$EMP_ID&ok_sync=$counts");
                    exit;
                } catch (\Throwable $e) {
                    if ($db->inTransaction()) $db->rollBack();
                    $msgErr = "Error en sincronización: " . $e->getMessage();
                }
            }
        }
    }
}

if (isset($_GET['ok_sync'])) $msg = "✅ {$_GET['ok_sync']} asientos sincronizados con éxito. Los balances ahora coinciden con el Excel.";
if (isset($_GET['ok'])) $msg = "✅ {$_GET['ok']} asiento(s) reubicados exitosamente.";

// ─────────────────────────────────────────────────────────────
// COMPARACIÓN EXCEL (2023)
// ─────────────────────────────────────────────────────────────
$compararExcel = isset($_GET['compare_excel']);
$excelData = [];
$discrepanciasExcel = ['sobran'=>[], 'faltan'=>[], 'descuadrados'=>[]];

if ($compararExcel) {
    $jsonPath = __DIR__ . '/database/excel_2023_cxc.json';
    if (file_exists($jsonPath)) {
        $excelRaw = json_decode(file_get_contents($jsonPath), true);
        foreach ($excelRaw as $row) {
            if ($row['conteo']) $excelData[$row['conteo']] = $row;
        }
    } else {
        $msgErr = "No se encontró database/excel_2023_cxc.json.";
    }
}

// ─────────────────────────────────────────────────────────────
// CALCULAR SALDOS
// ─────────────────────────────────────────────────────────────
$saldosPorCuenta = [];
$totalEnBalance  = 0.0;
$detalles        = [];

if (!empty($cuentaIds)) {
    $ph = implode(',', array_fill(0, count($cuentaIds), '?'));

    $stS = $db->prepare("
        SELECT p.id, p.codigo, p.nombre, p.naturaleza,
               COALESCE(SUM(CASE WHEN c.id IS NOT NULL THEN (a.debito-a.credito) ELSE 0 END),0) AS saldo_neto
        FROM puc_cuentas p
        LEFT JOIN asientos a ON a.cuenta_id=p.id AND a.empresa_id=p.empresa_id
        LEFT JOIN comprobantes c ON c.id=a.comprobante_id AND c.empresa_id=p.empresa_id
            AND c.estado='registrado' AND YEAR(COALESCE(a.fecha,c.fecha))<=?
        WHERE p.id IN ($ph) AND p.empresa_id=?
        GROUP BY p.id,p.codigo,p.nombre,p.naturaleza
        ORDER BY p.codigo
    ");
    $stS->execute(array_merge([$YEAR], $cuentaIds, [$EMP_ID]));
    foreach ($stS->fetchAll(PDO::FETCH_ASSOC) as $s) {
        $enB = ($s['naturaleza']==='C') ? -((float)$s['saldo_neto']) : (float)$s['saldo_neto'];
        $saldosPorCuenta[] = array_merge($s, ['en_balance'=>$enB]);
        $totalEnBalance += $enB;
    }

    $stD = $db->prepare("
        SELECT a.id AS asiento_id, c.id AS comprobante_id,
               COALESCE(a.fecha,c.fecha) AS fecha_efectiva,
               c.fecha AS fecha_comp, a.fecha AS fecha_asiento,
               YEAR(COALESCE(a.fecha,c.fecha)) AS anio_efectivo,
               p.id AS cuenta_id, p.codigo AS cuenta_codigo, p.nombre AS cuenta_nombre, p.naturaleza,
               a.debito, a.credito, (a.debito-a.credito) AS neto,
               a.descripcion, a.conteo, COALESCE(t.razon_social,'—') AS tercero
        FROM asientos a
        JOIN comprobantes c ON a.comprobante_id=c.id
        JOIN puc_cuentas p ON a.cuenta_id=p.id
        LEFT JOIN terceros t ON a.tercero_id=t.id
        WHERE a.cuenta_id IN ($ph) AND a.empresa_id=? AND c.estado='registrado'
          AND YEAR(COALESCE(a.fecha,c.fecha))<=?
        ORDER BY COALESCE(a.fecha,c.fecha) ASC, a.id ASC
    ");
    $stD->execute(array_merge($cuentaIds, [$EMP_ID, $YEAR]));
    $detalles = $stD->fetchAll(PDO::FETCH_ASSOC);

    if ($compararExcel) {
        $stFuturo = $db->prepare("
            SELECT a.id AS asiento_id, COALESCE(a.fecha,c.fecha) AS fecha_efectiva, a.conteo, a.debito, a.credito, (a.debito-a.credito) AS neto, p.codigo AS cuenta_codigo
            FROM asientos a JOIN comprobantes c ON a.comprobante_id=c.id JOIN puc_cuentas p ON a.cuenta_id=p.id
            WHERE a.cuenta_id IN ($ph) AND a.empresa_id=? AND c.estado='registrado' AND YEAR(COALESCE(a.fecha,c.fecha)) > ?
        ");
        $stFuturo->execute(array_merge($cuentaIds, [$EMP_ID, $YEAR]));
        $futuro = $stFuturo->fetchAll(PDO::FETCH_ASSOC);
        
        $dbConteos2023 = [];
        foreach ($detalles as $d) {
            if ($d['anio_efectivo'] == 2023) $dbConteos2023[$d['conteo']] = $d;
        }

        foreach ($detalles as $d) {
            if ($d['anio_efectivo'] == 2023 && $d['conteo'] && !isset($excelData[$d['conteo']])) {
                $discrepanciasExcel['sobran'][] = $d;
            }
        }

        $codigosSeleccionados = array_column($saldosPorCuenta, 'codigo');
        foreach ($excelData as $ct => $ex) {
            if (in_array($ex['acct'], $codigosSeleccionados)) {
                if (!isset($dbConteos2023[$ct])) {
                    $enFuturo = null;
                    foreach ($futuro as $f) { if ($f['conteo'] == $ct) { $enFuturo = $f; break; } }
                    $discrepanciasExcel['faltan'][] = ['excel' => $ex, 'db_futuro' => $enFuturo];
                }
            }
        }
    }
}

$diferencia = round($totalEnBalance - $TARGET, 2);

$porAnio = [];
foreach ($detalles as $d) {
    $a = (int)$d['anio_efectivo'];
    if (!isset($porAnio[$a])) $porAnio[$a] = ['debito'=>0,'credito'=>0,'neto'=>0,'filas'=>[]];
    $porAnio[$a]['debito']  += (float)$d['debito'];
    $porAnio[$a]['credito'] += (float)$d['credito'];
    $porAnio[$a]['neto']    += (float)$d['neto'];
    $porAnio[$a]['filas'][]  = $d;
}
ksort($porAnio);

$candidatos = [];
if (!empty($detalles) && abs($diferencia) >= $TOL) {
    foreach ($detalles as $d) {
        $nr = ($d['naturaleza']==='C') ? -((float)$d['neto']) : (float)$d['neto'];
        if (abs($nr - $diferencia) <= $TOL) $candidatos[] = $d;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>CxC Cuadre <?= $YEAR ?> · ContaFC</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:#0f172a;color:#e2e8f0;padding:22px;min-height:100vh;font-size:13px}
a.back{display:inline-flex;align-items:center;gap:5px;color:#60a5fa;font-size:12px;text-decoration:none;margin-bottom:14px}
a.back:hover{color:#93c5fd}
h1{font-size:19px;font-weight:900;color:#f8fafc}
.sub{color:#64748b;font-size:11px;margin:2px 0 18px}
.card{background:#1e293b;border:1px solid #334155;border-radius:12px;padding:16px 20px;margin-bottom:16px}
.ct{font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px}
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px;margin-bottom:16px}
.kpi{background:#1e293b;border:1px solid #334155;border-radius:12px;padding:14px 18px}
.kpi .lbl{font-size:10px;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700}
.kpi .val{font-size:22px;font-weight:900;margin-top:4px;font-family:'Courier New',monospace}
.k-ok .val{color:#4ade80}.k-err .val{color:#f87171}.k-blu .val{color:#60a5fa}.k-warn .val{color:#fbbf24}.k-gr .val{color:#94a3b8}
.alert{border-radius:8px;padding:10px 15px;margin-bottom:12px;font-size:12px;font-weight:600}
.a-ok{background:#14532d;border:1px solid #16a34a;color:#bbf7d0}
.a-err{background:#450a0a;border:1px solid #dc2626;color:#fecaca}
.a-warn{background:#3d2c00;border:1px solid #d97706;color:#fde68a}
.a-blue{background:#1e3a5f;border:1px solid #2563eb;color:#bfdbfe}
.a-purple{background:#2e1065;border:1px solid #7c3aed;color:#ddd6fe}
label.lbl2{font-size:11px;color:#94a3b8;font-weight:700}
select,input[type=number],input[type=date]{background:#0f172a;border:1px solid #475569;color:#e2e8f0;padding:6px 10px;border-radius:6px;font-size:12px}
.btn{padding:5px 13px;border:none;border-radius:6px;cursor:pointer;font-size:11px;font-weight:700;white-space:nowrap;transition:opacity .15s}
.btn:hover{opacity:.85}
.btn-blue{background:#2563eb;color:#fff}.btn-green{background:#16a34a;color:#fff}
.btn-orange{background:#d97706;color:#fff}.btn-slate{background:#475569;color:#fff}.btn-red{background:#dc2626;color:#fff}
.tbl-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:11px}
thead th{background:#0f172a;color:#94a3b8;padding:7px 9px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;white-space:nowrap;border-bottom:1px solid #334155}
tbody td{padding:5px 9px;border-bottom:1px solid #1e293b;vertical-align:middle}
tbody tr:hover td{background:#253348}
tfoot td{background:#0f172a;font-weight:800;padding:6px 9px;border-top:1px solid #475569}
.num{text-align:right;font-family:'Courier New',monospace;white-space:nowrap}
.y-head td{background:#0f172a;color:#60a5fa;font-weight:900;font-size:12px;padding:9px;border-top:2px solid #334155}
.badge{display:inline-block;padding:2px 7px;border-radius:20px;font-size:10px;font-weight:700}
.b-pos{background:#14532d;color:#4ade80}.b-neg{background:#450a0a;color:#f87171}
.b-d{background:#1e3a5f;color:#60a5fa}.b-c{background:#431407;color:#fb923c}
.hl td{background:#1a2e10!important}
.sticky{position:sticky;top:0;z-index:50;background:#1e293b;border:1px solid #334155;border-radius:8px;padding:8px 14px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:10px}
input[type=checkbox]{width:13px;height:13px;cursor:pointer;accent-color:#2563eb}
code{color:#60a5fa;font-size:10px}
.cg{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:8px;margin-top:10px}
.ci{background:#0f172a;border:1px solid #334155;border-radius:8px;padding:8px 12px;display:flex;align-items:center;gap:8px;cursor:pointer;transition:border-color .15s}
.ci:hover{border-color:#475569}.ci.on{border-color:#2563eb;background:#1e3a5f}
.pill{display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;margin-left:4px}
.p-ok{background:#14532d;color:#4ade80}.p-err{background:#450a0a;color:#f87171}
</style>
</head>
<body>

<a class="back" href="libros_oficiales.php">← Libros Oficiales</a>
<h1>🔬 Cuadre CxC — Balance General <?= $YEAR ?></h1>
<p class="sub"><strong style="color:#e2e8f0"><?= htmlspecialchars($empNombre) ?></strong></p>

<?php if ($msg):    ?><div class="alert a-ok">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($msgErr): ?><div class="alert a-err">❌ <?= htmlspecialchars($msgErr) ?></div><?php endif; ?>

<!-- ══ CONFIGURACIÓN ══ -->
<div class="card">
  <div class="ct">⚙️ Configuración</div>
  <form method="GET" id="fconfig">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;margin-bottom:14px">
      <div>
        <label class="lbl2">Empresa</label><br>
        <select name="eid" style="margin-top:4px">
          <?php foreach ($empresas as $e): ?>
            <option value="<?= $e['id'] ?>" <?= $e['id']==$EMP_ID?'selected':'' ?>>
              #<?= $e['id'] ?> – <?= htmlspecialchars($e['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="lbl2">Año BG</label><br>
        <input type="number" name="year" value="<?= $YEAR ?>" min="2000" max="2099" style="width:80px;margin-top:4px">
      </div>
      <div>
        <label class="lbl2">Target</label><br>
        <input type="number" name="target" value="<?= $TARGET ?>" step="0.01" style="width:150px;margin-top:4px">
      </div>
      <button type="submit" class="btn btn-blue" style="height:32px">🔄 Aplicar</button>
      <a href="?<?= http_build_query(array_merge($_GET, ['compare_excel'=>1])) ?>" class="btn btn-slate" style="height:32px;display:inline-flex;align-items:center;text-decoration:none">📊 Comparar Excel (2023)</a>
      <span class="pill <?= !empty($detalles)?'p-ok':'p-err' ?>"><?= count($detalles) ?> mov.</span>
    </div>

    <div class="cg">
      <?php foreach ($todasA as $ca):
          if (strlen($ca['codigo']) < 5) continue;
          $isSel = in_array((int)$ca['id'], $cuentaIds);
          $stSQ  = $db->prepare("SELECT COALESCE(SUM(CASE WHEN c.id IS NOT NULL THEN (a.debito-a.credito) ELSE 0 END),0) FROM asientos a JOIN comprobantes c ON c.id=a.comprobante_id AND c.estado='registrado' AND YEAR(COALESCE(a.fecha,c.fecha))<=? WHERE a.cuenta_id=? AND a.empresa_id=?");
          $stSQ->execute([$YEAR, $ca['id'], $EMP_ID]);
          $sal = (float)$stSQ->fetchColumn();
          if (abs($sal) < 0.01 && !$isSel) continue;
      ?>
        <label class="ci <?= $isSel?'on':'' ?>">
          <input type="checkbox" name="cids[]" value="<?= $ca['id'] ?>" <?= $isSel?'checked':'' ?> onchange="this.closest('label').classList.toggle('on',this.checked);setTimeout(()=>document.getElementById('fconfig').submit(),80)">
          <div style="flex:1">
            <div style="font-size:10px;color:#60a5fa"><code><?= $ca['codigo'] ?></code> <span class="badge <?= $ca['naturaleza']==='D'?'b-d':'b-c' ?>"><?= $ca['naturaleza'] ?></span></div>
            <div style="font-size:11px"><?= htmlspecialchars($ca['nombre']) ?></div>
            <div style="font-family:monospace;color:#4ade80"><?= number_format($sal,2) ?></div>
          </div>
        </label>
      <?php endforeach; ?>
    </div>
  </form>

  <!-- FORMULARIO DE SINCRONIZACIÓN (Separado para evitar anidamiento) -->
  <div style="margin-top:16px; padding-top:16px; border-top:1px solid #334155">
    <form method="POST" onsubmit="return confirm('⚠️ ¿Sincronizar TODAS las fechas basándose en el Excel?\n\nEsto actualizará miles de asientos en 2023, 2024 y 2025 para que coincidan exactamente con la fecha original del Excel.\n\n¿Deseas continuar?')">
      <input type="hidden" name="action" value="sync_all_dates">
      <button type="submit" class="btn btn-orange" style="height:40px; width:100%; font-size:14px">
        ⚡ EJECUTAR SINCRONIZACIÓN AUTOMÁTICA TOTAL (2023-2025)
      </button>
    </form>
  </div>
</div>

<!-- ══ RESULTADOS COMPARACIÓN EXCEL ══ -->
<?php if ($compararExcel): ?>
<div class="card" style="border-color:#60a5fa">
  <div class="ct" style="color:#60a5fa">📊 Resultado de Comparación con Excel (2023)</div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
    <div>
      <div class="alert a-purple" style="font-size:11px">⚠️ <strong>Sobran en DB 2023:</strong> <?= count($discrepanciasExcel['sobran']) ?> asientos.</div>
      <form method="POST">
        <input type="hidden" name="action" value="reubicar"><input type="hidden" name="nueva_fecha" value="2024-01-01">
        <div style="max-height:250px;overflow:auto"><table>
          <thead><tr><th><input type="checkbox" onclick="document.querySelectorAll('.cs-s').forEach(c=>c.checked=this.checked)"></th><th>ID</th><th>Neto</th></tr></thead>
          <tbody><?php foreach ($discrepanciasExcel['sobran'] as $s): ?><tr><td><input type="checkbox" name="ids[]" value="<?= $s['asiento_id'] ?>" class="cs-s"></td><td><?= $s['asiento_id'] ?></td><td class="num"><?= number_format($s['neto'],2) ?></td></tr><?php endforeach; ?></tbody>
        </table></div>
        <button type="submit" class="btn btn-orange" style="width:100%;margin-top:8px">🔀 Mover seleccionados a 2024</button>
      </form>
    </div>
    <div>
      <div class="alert a-blue" style="font-size:11px">🔍 <strong>Faltan en DB 2023:</strong> <?= count($discrepanciasExcel['faltan']) ?> registros.</div>
      <form method="POST">
        <input type="hidden" name="action" value="reubicar"><input type="hidden" name="nueva_fecha" value="2023-12-31">
        <div style="max-height:250px;overflow:auto"><table>
          <thead><tr><th>Sel</th><th>Conteo</th><th>Neto</th><th>Estado DB</th></tr></thead>
          <tbody><?php foreach ($discrepanciasExcel['faltan'] as $f): ?><tr><td><?php if ($f['db_futuro']): ?><input type="checkbox" name="ids[]" value="<?= $f['db_futuro']['asiento_id'] ?>"><?php else: ?>—<?php endif; ?></td><td><code><?= $f['excel']['conteo'] ?></code></td><td class="num"><?= number_format($f['excel']['debito']-$f['excel']['credito'],2) ?></td><td><?= $f['db_futuro']?'En '.$f['db_futuro']['fecha_efectiva']:'No existe' ?></td></tr><?php endforeach; ?></tbody>
        </table></div>
        <button type="submit" class="btn btn-blue" style="width:100%;margin-top:8px">📥 Traer seleccionados a 2023</button>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ══ KPIs ══ -->
<div class="kpis">
  <div class="kpi k-blu"><div class="lbl">🎯 Target</div><div class="val"><?= number_format($TARGET,2) ?></div></div>
  <div class="kpi <?= abs($diferencia)<$TOL?'k-ok':'k-err' ?>"><div class="lbl">📊 Balance BG <?= $YEAR ?></div><div class="val"><?= number_format($totalEnBalance,2) ?></div></div>
  <div class="kpi <?= abs($diferencia)<$TOL?'k-ok':'k-warn' ?>"><div class="lbl">⚖️ Diferencia</div><div class="val"><?= ($diferencia>=0?'+':'').number_format($diferencia,2) ?></div></div>
</div>

<!-- ══ TABLA COMPLETA ══ -->
<?php foreach ($porAnio as $anio => $data): ?>
<div class="card"><div class="ct">📅 AÑO <?= $anio ?> · <?= count($data['filas']) ?> mov · Neto: <?= number_format($data['neto'],2) ?></div>
<div class="tbl-wrap"><table>
  <thead><tr><th>ID</th><th>Conteo</th><th>F.Efectiva</th><th>Cuenta</th><th>Descripción</th><th class="num">Neto</th></tr></thead>
  <tbody><?php foreach ($data['filas'] as $f): ?><tr><td><?= $f['asiento_id'] ?></td><td><code><?= $f['conteo'] ?></code></td><td><?= $f['fecha_efectiva'] ?></td><td><code><?= $f['cuenta_codigo'] ?></code></td><td style="color:#94a3b8"><?= htmlspecialchars($f['descripcion']) ?></td><td class="num"><?= number_format((float)$f['neto'],2) ?></td></tr><?php endforeach; ?></tbody>
</table></div></div>
<?php endforeach; ?>

</body>
</html>
