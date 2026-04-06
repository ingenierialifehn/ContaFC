<?php
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Database;

try {
    $db = Database::getInstance()->getPdo();
    $res = $db->query("SELECT * FROM periodos WHERE empresa_id = 1")->fetchAll(PDO::FETCH_ASSOC);
    echo "📅 Períodos para empresa 1:\n";
    foreach ($res as $periodo) {
        echo "ID: {$periodo['id']} - Año: {$periodo['anio']} - Mes: {$periodo['mes']} - Estado: {$periodo['estado']}\n";
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
