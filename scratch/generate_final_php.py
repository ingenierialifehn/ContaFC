import json
import os

base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
json_path = os.path.join(base_dir, 'database', 'excel_full_2023.json')

with open(json_path, 'r', encoding='utf-8') as f:
    data = json.load(f)

php_data = "[\n"
for item in data:
    php_data += f"  ['debito'=>{item['debito']}, 'credito'=>{item['credito']}, 'desc'=>'REST: {item['desc']}', 'fecha'=>'{item['fecha']}', 'conteo'=>{item['conteo']}],\n"
php_data += "]"

php_code = f"""<?php
require_once __DIR__ . '/bootstrap.php';
$db = \\ContaFC\\Core\\Database::getInstance()->getPdo();
$empId = 1;

echo "<h3>MODO ESPEJO TOTAL 2023 (DATOS INTEGRADOS)</h3>";

$excelFull2023 = {php_data};

try {{
    $stAcc = $db->prepare("SELECT id FROM puc_cuentas WHERE codigo = '11050101' AND empresa_id = ? LIMIT 1");
    $stAcc->execute([$empId]);
    $cuentaId = $stAcc->fetchColumn();

    $db->beginTransaction();

    $stDel = $db->prepare("DELETE FROM asientos WHERE empresa_id = ? AND cuenta_id = ? AND YEAR(fecha) = 2023");
    $stDel->execute([$empId, $cuentaId]);
    echo "🗑️ Limpieza de 2023 completada.<br>";

    $stComp = $db->prepare("SELECT id FROM comprobantes WHERE numero = 'REPXCEL' LIMIT 1");
    $stComp->execute();
    $compId = $stComp->fetchColumn();

    $sqlIns = "INSERT INTO asientos (empresa_id, comprobante_id, cuenta_id, linea, debito, credito, descripcion, fecha, conteo) 
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stIns = $db->prepare($sqlIns);
    
    $insertados = 0;
    foreach ($excelFull2023 as $ex) {{
        $stIns->execute([$empId, $compId, $cuentaId, $insertados+1, $ex['debito'], $ex['credito'], $ex['desc'], $ex['fecha'], $ex['conteo']]);
        $insertados++;
    }}

    $db->commit();
    echo "✅ Éxito Final: Se restauraron los $insertados registros oficiales de 2023.<br>";
    echo "<b>¡POR FIN! Ahora recarga analizar_cxc.php.</b>";

}} catch (Exception $e) {{
    if ($db->inTransaction()) $db->rollBack();
    echo "Error: " . $e->getMessage();
}}
?>"""

target_php = os.path.join(base_dir, 'diagnostico.php')
# We need to handle potentially DIFFERENT path for XAMPP
xampp_php = "C:\\xampp\\htdocs\\ContaFC\\diagnostico.php"

with open(target_php, 'w', encoding='utf-8') as f:
    f.write(php_code)

print(f"PHP actualizado con datos integrados en {target_php}")
