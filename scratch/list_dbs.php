<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("SHOW DATABASES");
echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN), JSON_PRETTY_PRINT);
