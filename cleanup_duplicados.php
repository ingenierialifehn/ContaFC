<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();
$eid = 1;
$compId = 9999; // El comprobante del backup masivo

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>
<style>body{font-family:sans-serif;font-size:13px;padding:20px;max-width:900px;margin:0 auto}
table{border-collapse:collapse;width:100%} th,td{border:1px solid #ccc;padding:4px 8px}
th{background:#eee} .ok{background:#d1fae5} .del{background:#fee2e2}
h2{margin-top:25px;border-bottom:2px solid #333;padding:5px}
.btn{display:inline-block;padding:10px 25px;background:#dc2626;color:white;border:none;font-size:15px;
  cursor:pointer;border-radius:5px;text-decoration:none;font-weight:700;margin:10px 0}
.btnok{background:#16a34a}
pre{background:#1e293b;color:#a3e635;padding:10px;font-size:11px;overflow-x:auto}
.warn{background:#fef9c3;padding:10px;border-left:4px solid #d97706;margin:10px 0}</style></head><body>";

echo "<h1>🧹 Limpieza de importaciones duplicadas – Comprobante #$compId</h1>";

// ── RESUMEN POR BATCH ─────────────────────────────────────────────────────
echo "<h2>1. Batches detectados en comprobante $compId</h2>";
$stmt = $db->query("SELECT 
    DATE(created_at) as dia,
    LEFT(created_at, 16) as hora_approx,
    MIN(id) as id_min, MAX(id) as id_max,
    COUNT(*) as total
  FROM asientos
  WHERE comprobante_id = $compId
  GROUP BY LEFT(created_at, 16)
  ORDER BY created_at");
$batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table><tr><th>Día</th><th>Hora (aprox)</th><th>ID mín</th><th>ID máx</th><th>Total asientos</th><th>Acción</th></tr>";
$batchNum = 0;
$batchInfo = [];
foreach ($batches as $b) {
    $batchNum++;
    $accion = $batchNum === 1 ? "✅ MANTENER" : "❌ ELIMINAR";
    $cls = $batchNum === 1 ? 'ok' : 'del';
    $batchInfo[] = $b;
    echo "<tr class='$cls'><td>{$b['dia']}</td><td>{$b['hora_approx']}</td>
        <td>{$b['id_min']}</td><td>{$b['id_max']}</td>
        <td><b>{$b['total']}</b></td><td><b>$accion</b></td></tr>";
}
echo "</table>";

// ── CUÁNTOS SE VAN A BORRAR ───────────────────────────────────────────────
echo "<h2>2. Resumen de lo que se eliminará</h2>";
if (count($batchInfo) <= 1) {
    echo "<p style='color:green'>✅ Solo hay un batch. No hay duplicados que eliminar.</p>";
    echo "</body></html>"; exit;
}

// El primer batch es el que conservamos (fecha del primer created_at)
$keepDate = substr($batchInfo[0]['hora_approx'], 0, 16);
$stmt = $db->prepare("SELECT COUNT(*) FROM asientos WHERE comprobante_id = :cid AND LEFT(created_at,16) > :dt");
$stmt->execute([':cid' => $compId, ':dt' => $keepDate]);
$toDelete = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM asientos WHERE comprobante_id = :cid AND LEFT(created_at,16) = :dt");
$stmt->execute([':cid' => $compId, ':dt' => $keepDate]);
$toKeep = $stmt->fetchColumn();

echo "<p>✅ <b>Asientos a conservar</b> (primer batch, {$batchInfo[0]['hora_approx']}): <b>$toKeep</b></p>";
echo "<p>❌ <b>Asientos a eliminar</b> (duplicados): <b>$toDelete</b></p>";

// ── IMPACTO EN EL BALANCE ANTES/DESPUÉS ──────────────────────────────────
echo "<h2>3. Impacto estimado en Balance General 2023</h2>";
echo "<p><em>Saldo ACTUAL (todos los batches) vs. Saldo ESPERADO (solo primer batch):</em></p>";
$sql = "SELECT p.codigo, p.nombre, p.naturaleza, p.tipo_cuenta,
    SUM(CASE WHEN c.id IS NOT NULL THEN (a.debito-a.credito) ELSE 0 END) as saldo_completo,
    SUM(CASE WHEN c.id IS NOT NULL AND LEFT(a.created_at,16) <= :dt THEN (a.debito-a.credito) ELSE 0 END) as saldo_postfix
  FROM puc_cuentas p
  LEFT JOIN asientos a ON a.cuenta_id=p.id AND a.empresa_id=p.empresa_id AND a.comprobante_id=$compId
  LEFT JOIN comprobantes c ON c.id=a.comprobante_id AND c.empresa_id=p.empresa_id AND c.estado='registrado' AND YEAR(c.fecha)<=2023
  WHERE p.empresa_id=$eid AND p.tipo_cuenta IN ('A','P','R') AND p.activa=1
  GROUP BY p.id, p.codigo
  HAVING ABS(saldo_completo)>0.001 OR ABS(saldo_postfix)>0.001
  ORDER BY p.codigo";
$rows = $db->prepare($sql);
$rows->execute([':dt' => $keepDate]);
$impact = $rows->fetchAll(PDO::FETCH_ASSOC);

echo "<table><tr><th>Código</th><th>Nombre</th><th>Tipo</th>
    <th>Saldo actual<br>(3 batches)</th><th>Saldo esperado<br>(1 batch)</th><th>Diferencia</th></tr>";
foreach ($impact as $r) {
    $act = (float)$r['saldo_completo'];
    $esp = (float)$r['saldo_postfix'];
    $dif = $act - $esp;
    $cls = abs($dif) > 0.01 ? "style='background:#fff7ed'" : "";
    echo "<tr $cls><td>{$r['codigo']}</td><td>{$r['nombre']}</td><td>{$r['tipo_cuenta']}</td>
        <td align='right'>".number_format($act,2)."</td>
        <td align='right'>".number_format($esp,2)."</td>
        <td align='right' style='color:".($dif!=0?'red':'')."'>".number_format($dif,2)."</td></tr>";
}
echo "</table>";

// ── SQL A EJECUTAR ─────────────────────────────────────────────────────────
echo "<h2>4. SQL de limpieza</h2>";
echo "<pre>-- Verificar antes de ejecutar:
SELECT COUNT(*) FROM asientos WHERE comprobante_id = $compId AND LEFT(created_at,16) > '$keepDate';

-- Eliminar batches duplicados (conserva el primer batch del $keepDate):
DELETE FROM asientos WHERE comprobante_id = $compId AND LEFT(created_at,16) > '$keepDate';</pre>";

// ── BOTÓN DE EJECUCIÓN ────────────────────────────────────────────────────
echo "<h2>5. Ejecutar limpieza</h2>";

if (isset($_GET['ejecutar']) && $_GET['ejecutar'] === 'SI_CONFIRMO') {
    // --- EJECUTAR ---
    $db->beginTransaction();
    try {
        // Verificar no hay cxc
        $cxc = $db->prepare("SELECT COUNT(*) FROM asientos a
            JOIN puc_cuentas p ON p.id=a.cuenta_id
            WHERE a.comprobante_id=:cid AND LEFT(a.created_at,16)>:dt
              AND p.codigo IN ('110301','11050101')");
        $cxc->execute([':cid'=>$compId, ':dt'=>$keepDate]);
        if ($cxc->fetchColumn() > 0) {
            throw new Exception("¡ALERTA! Hay asientos de CxC (110301/11050101) que se borrarían. Abortando.");
        }
        $del = $db->prepare("DELETE FROM asientos WHERE comprobante_id=:cid AND LEFT(created_at,16)>:dt");
        $del->execute([':cid'=>$compId, ':dt'=>$keepDate]);
        $affected = $del->rowCount();
        $db->commit();
        echo "<div style='background:#d1fae5;padding:20px;border-radius:8px;font-size:16px'>
            ✅ <b>¡Listo!</b> Se eliminaron <b>$affected</b> asientos duplicados del comprobante #$compId.<br>
            El balance general debería mostrar los valores correctos ahora.<br><br>
            <a href='debug_balance.php' class='btn btnok'>Verificar balance</a>
        </div>";
    } catch (Exception $e) {
        $db->rollBack();
        echo "<div style='background:#fee2e2;padding:15px'>❌ Error: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div class='warn'>⚠️ <b>Esta operación eliminará {$toDelete} asientos.</b> Asegúrate de tener un respaldo antes de continuar.</div>";
    echo "<a class='btn' href='?ejecutar=SI_CONFIRMO' onclick=\"return confirm('¿Estás seguro? Se eliminarán {$toDelete} asientos duplicados.');\">
        🗑️ Eliminar {$toDelete} asientos duplicados
    </a>";
    echo "<p style='color:#666;margin-top:10px'>O ejecuta manualmente en tu DB:<br>
    <code>DELETE FROM asientos WHERE comprobante_id = $compId AND LEFT(created_at,16) > '$keepDate';</code></p>";
}

echo "</body></html>";
