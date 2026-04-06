<?php
require_once __DIR__ . '/bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
header('Content-Type: application/json');
echo json_encode([
    'database' => $db->query("SELECT DATABASE()")->fetchColumn(),
    'tables' => $tables
], JSON_PRETTY_PRINT);
