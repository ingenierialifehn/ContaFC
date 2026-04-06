<?php
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();
$res = $db->query("SELECT (SELECT COUNT(*) FROM comprobantes) as c, (SELECT COUNT(*) FROM asientos) as a")->fetch();
echo "Comprobantes: " . $res['c'] . " | Asientos: " . $res['a'] . "\n";
