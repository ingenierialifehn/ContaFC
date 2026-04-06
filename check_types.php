<?php
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Database;

try {
    $db = Database::getInstance()->getPdo();
    $res = $db->query("SELECT * FROM tipos_comprobante WHERE empresa_id = 1")->fetchAll(PDO::FETCH_ASSOC);
    echo "📄 Tipos de Comprobante para empresa 1:\n";
    foreach ($res as $tipo) {
        echo "ID: {$tipo['id']} - Código: {$tipo['codigo']} - Nombre: {$tipo['nombre']}\n";
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
