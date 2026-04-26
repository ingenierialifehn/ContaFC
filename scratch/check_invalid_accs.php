<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT COUNT(*) FROM asientos a LEFT JOIN puc_cuentas p ON a.cuenta_id = p.id WHERE p.id IS NULL")->fetchColumn();
echo "Invalid accounts in entries: $res\n";
