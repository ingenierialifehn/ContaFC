<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "TIMESTAMP AUDIT FOR 10083\n";
$stmt = $db->query("SELECT created_at, COUNT(*) as qty FROM asientos WHERE comprobante_id = 10083 GROUP BY created_at");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
