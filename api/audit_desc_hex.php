<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "EXACT DESCRIPTION AUDIT FOR TERRENOS\n";
$stmt = $db->query("SELECT id, HEX(descripcion) as hex_desc, descripcion FROM asientos WHERE id IN (90916, 94443)");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
