<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstancepdo();
$db = Database::getInstance()->getPdo();

echo "DUPLICATE AUDIT FOR COMPROBANTE 10607\n";
$stmt = $db->query("
    SELECT id, cuenta_id, debito, credito, descripcion, created_at
    FROM asientos
    WHERE comprobante_id = 10607
");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
