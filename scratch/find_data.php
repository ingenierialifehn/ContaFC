<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("SELECT TABLE_SCHEMA, TABLE_NAME, TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_ROWS > 100");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
