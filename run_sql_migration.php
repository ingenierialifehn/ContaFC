<?php
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Database;

$sqlFile = __DIR__ . '/database/migracion_asientos.sql';
if (!file_exists($sqlFile)) {
    die("❌ Archivo SQL no encontrado: $sqlFile\n");
}

echo "📽️ Leyendo SQL de migración...\n";
$sql = file_get_contents($sqlFile);

try {
    $db = Database::getInstance()->getPdo();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "⚙️ Ejecutando migración en MySQL...\n";
    // Podríamos usar exec() directo pero a veces es muy grande para un solo statement.
    // Usaremos un truco para separar los bloques de INSERT.
    
    $queries = explode(";", $sql);
    $count = 0;
    foreach ($queries as $q) {
        $q = trim($q);
        if ($q === '') continue;
        try {
            $db->exec($q);
            $count++;
        } catch (Throwable $e) {
            echo "⚠️ Advertencia en Query $count: " . $e->getMessage() . "\n";
            // Continuar si es IGNORE
        }
    }
    
    echo "✅ Migración finalizada. Se ejecutaron $count bloques de SQL.\n";
    
    $check = $db->query("SELECT COUNT(*) FROM asientos WHERE comprobante_id = 9999")->fetchColumn();
    echo "📊 Verificación final: Se encontraron $check asientos en el comprobante 9999.\n";

} catch (Throwable $e) {
    echo "❌ ERROR FATAL: " . $e->getMessage() . "\n";
}
