<?php
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Database;

try {
    $db = Database::getInstance()->getPdo();
    $res = $db->query("SELECT id, nombre, nit FROM empresas")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($res as $emp) {
        echo "ID: [{$emp['id']}] - Name: [{$emp['nombre']}] - NIT: [{$emp['nit']}]\n";
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
