<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "AUDITANDO DESCRIPCIÓN EXACTA (HEX)\n";
$stmt = $db->query("SELECT id, descripcion, HEX(descripcion) as hex_desc FROM asientos WHERE id = 90861");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
?>
