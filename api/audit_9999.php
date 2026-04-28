<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "VOUCHER 9999 AUDIT FOR BCO OCCIDENTE\n";
$stmt = $db->query("SELECT id, debito, credito, descripcion FROM asientos WHERE comprobante_id = 9999 AND cuenta_id = 85");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
