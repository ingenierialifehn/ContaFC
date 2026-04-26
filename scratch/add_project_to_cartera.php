<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();

try {
    $db->exec("ALTER TABLE cartera_creditos ADD COLUMN proyecto_id INT(11) NULL AFTER tercero_id");
    echo "Columna proyecto_id añadida con éxito.\n";
} catch (Exception $e) {
    echo "Error o ya existe: " . $e->getMessage() . "\n";
}
