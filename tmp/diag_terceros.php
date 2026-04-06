<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$eid = \ContaFC\Core\Auth::empresaId();

echo "Diagnostic for Empresa ID: $eid\n";
try {
    $countTotal = $db->query("SELECT COUNT(*) FROM terceros")->fetchColumn();
    $countEmpresa = $db->query("SELECT COUNT(*) FROM terceros WHERE empresa_id = $eid")->fetchColumn();
    $countActivos = $db->query("SELECT COUNT(*) FROM terceros WHERE empresa_id = $eid AND activo = 1")->fetchColumn();
    
    echo "Total registros en tabla: $countTotal\n";
    echo "Registros para esta empresa ($eid): $countEmpresa\n";
    echo "Registros activos para esta empresa: $countActivos\n";
    
    if ($countActivos > 0) {
        $sample = $db->query("SELECT id, codigo, razon_social, tipo_tercero FROM terceros WHERE empresa_id = $eid AND activo = 1 LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        echo "Muestra de 5 activos:\n";
        print_r($sample);
    } else {
        echo "No se encontraron activos. Revisando si hay inactivos o de otras empresas...\n";
        $others = $db->query("SELECT DISTINCT empresa_id FROM terceros")->fetchAll(PDO::FETCH_COLUMN);
        echo "Empresas con datos en terceros: " . implode(', ', $others) . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
