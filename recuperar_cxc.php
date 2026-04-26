<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
$db = Database::getInstance()->getPdo();
$EMP_ID = Auth::empresaId();

$msg = $msgErr = '';
$preview = [];

// ── Buscar los asientos dañados ──
// Son asientos en cuenta 11050101 (o cualquier CxC clientes) 
// cuya a.fecha fue puesta a 2024-01-01 por el bulk accidental
$stDanos = $db->prepare("
    SELECT 
        a.id,
        a.fecha AS fecha_actual,
        c.id AS comp_id,
        c.fecha AS fecha_comp,
        c.observaciones,
        p.codigo,
        p.nombre,
        a.debito,
        a.credito,
        (a.debito - a.credito) AS neto
    FROM asientos a
    JOIN comprobantes c ON a.comprobante_id = c.id
    JOIN puc_cuentas p ON a.cuenta_id = p.id
    WHERE a.empresa_id = :eid
      AND c.estado = 'registrado'
      AND YEAR(a.fecha) >= 2024          -- fecha asiento fue cambiada a 2024+
      AND YEAR(c.fecha) >= 2024           -- comprobante también es 2024+
      AND c.observaciones LIKE 'MIG-%'   -- comprobante creado por nuestra herramienta
      AND p.codigo LIKE '11050%'          -- cuentas CxC clientes (ajusta si es diferente)
    ORDER BY a.id ASC
");
$stDanos->execute([':eid' => $EMP_ID]);
$danos = $stDanos->fetchAll(PDO::FETCH_ASSOC);

// Si no hay con código 11050%, buscar de forma más amplia
if (empty($danos)) {
    $stDanos2 = $db->prepare("
        SELECT 
            a.id,
            a.fecha AS fecha_actual,
            c.id AS comp_id,
            c.fecha AS fecha_comp,
            c.observaciones,
            p.codigo,
            p.nombre,
            a.debito,
            a.credito,
            (a.debito - a.credito) AS neto
        FROM asientos a
        JOIN comprobantes c ON a.comprobante_id = c.id
        JOIN puc_cuentas p ON a.cuenta_id = p.id
        WHERE a.empresa_id = :eid
          AND c.estado = 'registrado'
          AND YEAR(a.fecha) >= 2024
          AND c.observaciones LIKE 'MIG-%'
          AND (
              p.codigo LIKE '13%'
              OR (p.nombre LIKE '%clientes%' OR p.nombre LIKE '%cobrar%')
          )
        ORDER BY a.id ASC
    ");
    $stDanos2->execute([':eid' => $EMP_ID]);
    $danos = $stDanos2->fetchAll(PDO::FETCH_ASSOC);
}

// ── ACCIÓN: Restaurar ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'restaurar') {
        // Restaurar todas las a.fecha afectadas a 2023-12-31
        // (una fecha válida en 2023 para que vuelvan al balance 2023)
        $fecha_restaurar = trim($_POST['fecha_restaurar'] ?? '2023-12-31');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_restaurar)) {
            $msgErr = "Fecha de restauración inválida.";
        } else {
            $ids_danos = array_column($danos, 'id');
            if (empty($ids_danos)) {
                $msgErr = "No se encontraron asientos para restaurar.";
            } else {
                $db->beginTransaction();
                try {
                    $pls = implode(',', array_fill(0, count($ids_danos), '?'));
                    // Restaurar a.fecha a la fecha indicada
                    $stR = $db->prepare("UPDATE asientos SET fecha = ? WHERE id IN ($pls)");
                    $stR->execute(array_merge([$fecha_restaurar], $ids_danos));
                    $db->commit();
                    $msg = "✅ " . count($ids_danos) . " asientos restaurados a fecha $fecha_restaurar. Recarga el Balance General.";
                    header("Location: {$_SERVER['PHP_SELF']}?ok=" . count($ids_danos) . "&eid={$EMP_ID}");
                    exit;
                } catch (\Throwable $e) {
                    $db->rollBack();
                    $msgErr = "Error: " . $e->getMessage();
                }
            }
        }
    }

    if ($action === 'restaurar_ids') {
        // Restaurar IDs específicos
        $ids = array_map('intval', (array)($_POST['ids'] ?? []));
        $fecha_restaurar = trim($_POST['fecha_restaurar'] ?? '2023-12-31');
        if (!empty($ids)) {
            $db->beginTransaction();
            try {
                $pls = implode(',', array_fill(0, count($ids), '?'));
                $db->prepare("UPDATE asientos SET fecha = ? WHERE id IN ($pls)")
                   ->execute(array_merge([$fecha_restaurar], $ids));
                $db->commit();
                $msg = "✅ " . count($ids) . " asientos restaurados a $fecha_restaurar.";
                header("Location: {$_SERVER['PHP_SELF']}?ok=" . count($ids) . "&eid={$EMP_ID}");
                exit;
            } catch (\Throwable $e) {
                $db->rollBack();
                $msgErr = "Error: " . $e->getMessage();
            }
        }
    }
}

if (isset($_GET['ok'])) $msg = "✅ {$_GET['ok']} asientos restaurados. Verifica el Balance General ahora.";

// ── Calcular total de los dañados ──
$totalDano = array_sum(array_column($danos, 'neto'));
$compsMIG  = [];
foreach ($danos as $d) {
    $compsMIG[$d['comp_id']] = $d['observaciones'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>🚨 RECUPERACIÓN CxC · ContaFC</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:#0f172a;color:#e2e8f0;padding:24px;min-height:100vh}
a.back{display:inline-flex;align-items:center;gap:5px;color:#60a5fa;font-size:12px;text-decoration:none;margin-bottom:14px}
h1{font-size:20px;font-weight:900;color:#f87171;margin-bottom:4px}
.sub{color:#64748b;font-size:12px;margin-bottom:20px}
.card{background:#1e293b;border:1px solid #334155;border-radius:12px;padding:18px 20px;margin-bottom:16px}
.ct{font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px}
.alert{border-radius:8px;padding:12px 16px;margin-bottom:14px;font-size:12px;font-weight:600}
.a-ok{background:#14532d;border:1px solid #16a34a;color:#bbf7d0}
.a-err{background:#450a0a;border:1px solid #dc2626;color:#fecaca}
.a-warn{background:#3d2c00;border:1px solid #d97706;color:#fde68a;font-size:13px;line-height:1.7}
.a-red{background:#450a0a;border:2px solid #dc2626;color:#fecaca;padding:16px;border-radius:10px;margin-bottom:16px;font-size:13px;line-height:1.8}
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;margin-bottom:16px}
.kpi{background:#1e293b;border:1px solid #334155;border-radius:12px;padding:14px 18px}
.kpi .lbl{font-size:10px;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:700}
.kpi .val{font-size:22px;font-weight:900;margin-top:4px;font-family:'Courier New',monospace}
.k-err .val{color:#f87171}.k-ok .val{color:#4ade80}.k-warn .val{color:#fbbf24}.k-gr .val{color:#94a3b8}
input[type=date]{background:#0f172a;border:1px solid #475569;color:#e2e8f0;padding:6px 10px;border-radius:6px;font-size:12px}
.btn{padding:8px 20px;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:800;white-space:nowrap}
.btn-red{background:#dc2626;color:#fff;font-size:14px;padding:10px 24px}
.btn-red:hover{background:#b91c1c}
.btn-blue{background:#2563eb;color:#fff}.btn-slate{background:#475569;color:#fff}
table{width:100%;border-collapse:collapse;font-size:11px}
thead th{background:#0f172a;color:#94a3b8;padding:7px 9px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;border-bottom:1px solid #334155;white-space:nowrap}
tbody td{padding:5px 9px;border-bottom:1px solid #1e293b;vertical-align:middle}
tbody tr:hover td{background:#253348}
tfoot td{background:#0f172a;font-weight:800;padding:6px 9px;border-top:1px solid #475569}
.num{text-align:right;font-family:'Courier New',monospace}
code{color:#f87171;font-size:11px}
input[type=checkbox]{width:13px;height:13px;cursor:pointer;accent-color:#2563eb}
</style>
</head>
<body>

<a class="back" href="analizar_cxc.php">← Volver a Cuadre CxC</a>
<h1>🚨 Recuperación de Datos CxC</h1>
<p class="sub">Script de emergencia para restaurar asientos afectados por reubicar masivo accidental</p>

<?php if ($msg):    ?><div class="alert a-ok">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($msgErr): ?><div class="alert a-err">❌ <?= htmlspecialchars($msgErr) ?></div><?php endif; ?>

<?php if (!empty($danos)): ?>

<!-- Diagnóstico -->
<div class="a-red">
  ⚠️ <strong>Se encontraron asientos afectados.</strong><br>
  Estos asientos tienen <code>a.fecha ≥ 2024</code> pero pertenecen a comprobantes <code>MIG-xxxx</code>
  creados por la herramienta. Fueron movidos accidentalmente. <br>
  Al restaurarlos a una fecha 2023, volverán a aparecer en el Balance General 2023.
</div>

<!-- KPIs -->
<div class="kpis">
  <div class="kpi k-err"><div class="lbl">Asientos afectados</div><div class="val"><?= count($danos) ?></div></div>
  <div class="kpi k-warn"><div class="lbl">Neto total movido</div><div class="val"><?= number_format($totalDano,2) ?></div></div>
  <div class="kpi k-gr"><div class="lbl">Comprobantes MIG</div><div class="val"><?= count($compsMIG) ?></div></div>
</div>

<!-- Acción principal de recuperación -->
<div class="card">
  <div class="ct">🔧 Restaurar TODOS los asientos afectados</div>
  <p style="color:#94a3b8;font-size:12px;margin-bottom:14px;line-height:1.7">
    Esto pondrá <code>a.fecha = {fecha_restaurar}</code> en los <?= count($danos) ?> asientos afectados,
    restaurando su presencia en el Balance General del año indicado.<br>
    <strong style="color:#fbbf24">⚠️ Usa una fecha dentro del año 2023 (ej. 2023-12-31).</strong>
  </p>
  <form method="POST" onsubmit="return confirm('¿Restaurar <?= count($danos) ?> asientos a la fecha indicada?')">
    <input type="hidden" name="action" value="restaurar">
    <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
      <div>
        <label style="font-size:11px;color:#94a3b8;font-weight:700;display:block;margin-bottom:4px">
          Fecha a restaurar:
        </label>
        <input type="date" name="fecha_restaurar" value="2023-12-31" required>
      </div>
      <button type="submit" class="btn btn-red">
        🔄 RESTAURAR <?= count($danos) ?> ASIENTOS
      </button>
    </div>
  </form>
</div>

<!-- Lista de comprobantes MIG encontrados -->
<div class="card">
  <div class="ct">📋 Comprobantes MIG involucrados</div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <?php foreach ($compsMIG as $cid => $cobs): ?>
      <span style="background:#0f172a;border:1px solid #dc2626;padding:4px 10px;border-radius:6px;font-size:11px">
        <code><?= htmlspecialchars($cobs) ?></code> (comp #<?= $cid ?>)
      </span>
    <?php endforeach; ?>
  </div>
</div>

<!-- Detalle de asientos -->
<div class="card">
  <div class="ct">📄 Detalle de asientos a restaurar (<?= count($danos) ?> registros)</div>
  <div style="overflow-x:auto;max-height:500px;overflow-y:auto">
    <table>
      <thead><tr>
        <th>ID</th><th>F.Actual (a.fecha)</th><th>F.Comp</th>
        <th>Comp.Obs</th><th>Código</th><th>Nombre</th>
        <th class="num">Débito</th><th class="num">Crédito</th><th class="num">Neto</th>
      </tr></thead>
      <tbody>
      <?php foreach ($danos as $d): ?>
        <tr>
          <td><?= $d['id'] ?></td>
          <td style="color:#f87171"><?= $d['fecha_actual'] ?></td>
          <td><?= $d['fecha_comp'] ?></td>
          <td><code><?= htmlspecialchars($d['observaciones']) ?></code></td>
          <td style="color:#60a5fa;font-size:10px"><?= $d['codigo'] ?></td>
          <td><?= htmlspecialchars($d['nombre']) ?></td>
          <td class="num"><?= $d['debito']!=0?number_format((float)$d['debito'],2):'—' ?></td>
          <td class="num"><?= $d['credito']!=0?number_format((float)$d['credito'],2):'—' ?></td>
          <td class="num"><?= number_format((float)$d['neto'],2) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot><tr>
        <td colspan="8">TOTAL NETO</td>
        <td class="num"><?= number_format($totalDano,2) ?></td>
      </tr></tfoot>
    </table>
  </div>
</div>

<?php else: ?>

<div class="alert a-ok">
  ✅ <strong>No se encontraron asientos dañados</strong> con los criterios actuales.<br>
  Los criterios buscan: asientos con <code>a.fecha ≥ 2024</code> en comprobantes <code>MIG-*</code>
  de cuentas tipo CxC clientes.<br><br>
  <strong>Si el balance no muestra "Cuentas por cobrar Clientes", puede deberse a:</strong>
  <ul style="margin-top:8px;padding-left:20px;line-height:2">
    <li>Los asientos tienen <code>a.fecha</code> en 2024 pero el comprobante no se llama MIG-*</li>
    <li>La cuenta tiene un código diferente (no 11050x)</li>
    <li>El balance está filtrado por proyecto y los asientos son de "General"</li>
  </ul>
</div>

<!-- Búsqueda amplia -->
<div class="card">
  <div class="ct">🔍 Búsqueda amplia — asientos en CxC con fecha ≥ 2024</div>
  <?php
    $stAmplio = $db->prepare("
        SELECT a.id, a.fecha AS fecha_a, c.fecha AS fecha_c, c.observaciones,
               p.codigo, p.nombre, (a.debito-a.credito) AS neto
        FROM asientos a
        JOIN comprobantes c ON a.comprobante_id=c.id
        JOIN puc_cuentas p ON a.cuenta_id=p.id
        WHERE a.empresa_id=:eid AND c.estado='registrado'
          AND YEAR(a.fecha) >= 2024
          AND (p.nombre LIKE '%clientes%' OR p.nombre LIKE '%cobrar%' OR p.codigo LIKE '11050%')
        ORDER BY a.id DESC LIMIT 200
    ");
    $stAmplio->execute([':eid' => $EMP_ID]);
    $amplio = $stAmplio->fetchAll(PDO::FETCH_ASSOC);
  ?>
  <?php if (!empty($amplio)): ?>
    <div class="alert a-warn" style="margin-bottom:12px">
      ⚠️ Se encontraron <?= count($amplio) ?> asientos con fecha 2024+ en cuentas CxC/Clientes.
      Probablemente estos son los que necesitas restaurar.
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="restaurar_ids">
      <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:10px">
        <label style="font-size:11px;color:#94a3b8;font-weight:700">Restaurar a:</label>
        <input type="date" name="fecha_restaurar" value="2023-12-31" required>
        <button type="submit" class="btn btn-red" onclick="return injectIds()">
          🔄 Restaurar seleccionados
        </button>
      </div>
      <div style="overflow-x:auto;max-height:400px;overflow-y:auto">
        <table>
          <thead><tr>
            <th><input type="checkbox" onclick="document.querySelectorAll('.rc').forEach(c=>c.checked=this.checked)" checked></th>
            <th>ID</th><th>F.Asiento</th><th>F.Comp</th>
            <th>Comp.Obs</th><th>Código</th><th>Nombre</th><th class="num">Neto</th>
          </tr></thead>
          <tbody>
          <?php foreach ($amplio as $r): ?>
            <tr>
              <td><input type="checkbox" class="rc" name="ids[]" value="<?= $r['id'] ?>" checked></td>
              <td><?= $r['id'] ?></td>
              <td style="color:#f87171"><?= $r['fecha_a'] ?></td>
              <td><?= $r['fecha_c'] ?></td>
              <td style="color:#94a3b8;font-size:10px"><?= htmlspecialchars($r['observaciones']) ?></td>
              <td style="color:#60a5fa;font-size:10px"><?= $r['codigo'] ?></td>
              <td><?= htmlspecialchars($r['nombre']) ?></td>
              <td class="num"><?= number_format((float)$r['neto'],2) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </form>
  <?php else: ?>
    <p style="color:#64748b;font-size:12px">No se encontraron asientos en cuentas CxC/Clientes con fecha ≥ 2024.</p>
  <?php endif; ?>
</div>
<?php endif; ?>

<script>
function injectIds(){
  const form = document.querySelector('form');
  const chk  = document.querySelectorAll('.rc:checked');
  if(!chk.length){ alert('Selecciona al menos un asiento.'); return false; }
  return confirm('¿Restaurar '+chk.length+' asientos a la fecha indicada?');
}
</script>
</body>
</html>
