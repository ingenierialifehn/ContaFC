<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "LINEA AUDIT FOR DUPLICATES IN COMPROBANTE 10083\n";
$stmt = $db->query("SELECT id, linea, debito, credito, descripcion FROM asientos WHERE comprobante_id = 10083 AND cuenta_id = 85");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
