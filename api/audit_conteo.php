<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "CONTEO AUDIT FOR 10083\n";
$stmt = $db->query("SELECT id, conteo FROM asientos WHERE id IN (8621, 8624, 8627, 8630)");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
