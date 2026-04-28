<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "TOP ACCOUNTS FOR EMPRESA 2\n";
$stmt = $db->query("SELECT id, codigo, nombre FROM puc_cuentas WHERE empresa_id = 2 LIMIT 20");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\nTOP ACCOUNTS FOR EMPRESA 1\n";
$stmt = $db->query("SELECT id, codigo, nombre FROM puc_cuentas WHERE empresa_id = 1 LIMIT 20");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
