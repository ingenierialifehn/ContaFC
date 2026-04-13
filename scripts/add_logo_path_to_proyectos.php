<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;

try {
    $db = Database::getInstance()->getPdo();
    $db->exec("ALTER TABLE proyectos ADD COLUMN logo_path VARCHAR(255) DEFAULT NULL AFTER nombre");
    echo "Columna logo_path agregada a la tabla proyectos.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
