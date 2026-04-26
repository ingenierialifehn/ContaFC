<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
echo $db->query("SELECT COUNT(*) FROM asientos WHERE conteo IS NULL")->fetchColumn() . "\n";
