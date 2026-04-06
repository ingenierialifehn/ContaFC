<?php
require_once __DIR__ . '/bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$ver = $db->query("SELECT VERSION()")->fetchColumn();
$cols = $db->query("DESCRIBE usuarios")->fetchAll();
header('Content-Type: application/json');
echo json_encode(['version' => $ver, 'columns' => $cols], JSON_PRETTY_PRINT);
