<?php
require_once __DIR__ . '/bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();

try {
    echo "Añadiendo índice a la columna 'conteo' en la tabla 'asientos'...\n";
    $db->exec("CREATE INDEX idx_asientos_conteo ON asientos(conteo)");
    echo "✅ Índice creado con éxito. Las actualizaciones ahora serán rápidas.\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
        echo "ℹ️ El índice ya existía.\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}
