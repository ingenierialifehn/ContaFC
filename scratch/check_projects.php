<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT proyecto_id, COUNT(*) as count FROM asientos GROUP BY proyecto_id")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
