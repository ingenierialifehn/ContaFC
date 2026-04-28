<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();
$stmt = $db->query("SELECT empresa_id, COUNT(*) as qty FROM asientos GROUP BY empresa_id");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
