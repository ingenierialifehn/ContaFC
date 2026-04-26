<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT COUNT(*) FROM asientos WHERE cuenta_id = 1")->fetchColumn();
echo "Entries for account 1: $res\n";
