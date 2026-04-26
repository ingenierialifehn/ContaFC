<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT total_debitos, total_creditos FROM comprobantes WHERE id = 9999")->fetch(PDO::FETCH_ASSOC);
print_r($res);
