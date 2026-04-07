<?php
require_once __DIR__ . '/bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$t = $db->query("DESCRIBE comprobantes")->fetchAll(PDO::FETCH_ASSOC);
print_r($t);
