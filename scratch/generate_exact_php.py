import json
import os

base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
json_path = os.path.join(base_dir, 'database', 'excel_fixed_exact.json')

with open(json_path, 'r', encoding='utf-8') as f:
    data = json.load(f)

# Convertir a formato PHP para el script self-contained
php_data = "[\n"
for item in data:
    php_data += f"  ['debito'=>{item['debito']}, 'credito'=>{item['credito']}, 'desc'=>'REST: {item['desc']}', 'fecha'=>'{item['fecha']}', 'conteo'=>{item['conteo']}],\n"
php_data += "]"

php_code = f"""<?php
require_once __DIR__ . '/bootstrap.php';
$db = \\ContaFC\\Core\\Database::getInstance()->getPdo();
$empId = 1;

echo "<h3>CUADRE CXC: NIVEL DE PRECISIÓN ABSOLUTA (0.00)</h3>";

$excelExactData = {php_data};

try {{
    $stAcc = $db->prepare("SELECT id FROM puc_cuentas WHERE codigo = '11050101' AND empresa_id = ? LIMIT 1");
    $stAcc->execute([$empId]);
    $cuentaId = $stAcc->fetchColumn();

    $db->beginTransaction();

    // 1. Limpieza total garantizada
    $stDel = $db->prepare("DELETE FROM asientos WHERE empresa_id = ? AND cuenta_id = ?");
    $stDel->execute([$empId, $cuentaId]);
    echo "🗑️ Limpieza TOTAL completada.<br>";

    // 2. Comprobante de restauración
    $stComp = $db->prepare("SELECT id FROM comprobantes WHERE numero = 'REPXCEL' AND empresa_id = ? LIMIT 1");
    $stComp->execute([$empId]);
    $compId = $stComp->fetchColumn();

    $sqlIns = "INSERT INTO asientos (empresa_id, comprobante_id, cuenta_id, linea, debito, credito, descripcion, fecha, conteo) 
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stIns = $db->prepare($sqlIns);
    
    $insertados = 0;
    foreach ($excelExactData as $ex) {{
        $stIns->execute([$empId, $compId, $cuentaId, $insertados+1, $ex['debito'], $ex['credito'], $ex['desc'], $ex['fecha'], $ex['conteo']]);
        $insertados++;
    }}

    $db->commit();
    echo "✅ Éxito Absoluto: Se restauraron los $insertados registros con precisión de centavos.<br>";
    echo "<b>¡ESTE ES EL MOMENTO! Refresca analizar_cxc.php. La diferencia tiene que ser 0.00.</b>";

}} catch (Exception $e) {{
    if ($db->inTransaction()) $db->rollBack();
    echo "Error: " . $e->getMessage();
}}
?>"""

target_php = os.path.join(base_dir, 'diagnostico.php')
with open(target_php, 'w', encoding='utf-8') as f:
    f.write(php_code)

print(f"PHP actualizado con PRECISIÓN ABSOLUTA en {target_php}")
