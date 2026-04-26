<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo json_encode($tables, JSON_PRETTY_PRINT);
