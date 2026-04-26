<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("SHOW CREATE TABLE asientos");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo $row['Create Table'];
