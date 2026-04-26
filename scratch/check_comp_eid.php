<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT empresa_id, COUNT(*) FROM comprobantes GROUP BY empresa_id")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
