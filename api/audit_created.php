<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "CREATED_AT AUDIT FOR 10083\n";
$stmt = $db->query("SELECT id, created_at FROM asientos WHERE comprobante_id = 10083 AND cuenta_id = 85");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
