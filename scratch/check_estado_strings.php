<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT DISTINCT QUOTE(estado) FROM comprobantes")->fetchAll(PDO::FETCH_COLUMN);
print_r($res);
