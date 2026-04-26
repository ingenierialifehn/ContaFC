<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT COUNT(*) FROM asientos WHERE debito IS NULL OR credito IS NULL")->fetchColumn();
echo "Null amounts: $res\n";
