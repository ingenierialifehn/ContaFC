<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "LINEA AUDIT FOR 10083 IDs\n";
$stmt = $db->query("SELECT id, linea, debito, credito FROM asientos WHERE id IN (8621, 8624, 8627, 8630)");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
