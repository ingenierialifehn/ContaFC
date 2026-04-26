<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Database;

if (php_sapi_name() !== 'cli' && !isset($_GET['run'])) {
    die("Corra este script desde CLI o agregue ?run=1 a la URL\n");
}

$db = Database::getInstance()->getPdo();
$sqlFile = __DIR__ . '/fix_fechas.sql';

if (!file_exists($sqlFile)) {
    die("Error: No se encontró fix_fechas.sql\n");
}

echo "Leyendo archivo SQL...\n";
$lines = file($sqlFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$count = 0;
$affected = 0;

$db->beginTransaction();
try {
    foreach ($lines as $line) {
        $stmt = $db->prepare($line);
        $stmt->execute();
        $affected += $stmt->rowCount();
        $count++;
        
        if ($count % 5000 == 0) {
            echo "Procesados $count / " . count($lines) . "...\n";
        }
    }
    $db->commit();
    echo "¡Completado! Total updates ejecutados: $count. Filas afectadas: $affected.\n";
} catch (\Exception $e) {
    $db->rollBack();
    echo "Error ejecutando SQL: " . $e->getMessage() . "\n";
}
