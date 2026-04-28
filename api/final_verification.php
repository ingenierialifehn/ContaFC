<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();
$stmt = $db->query("SELECT COUNT(*) FROM asientos WHERE empresa_id = 1");
echo "CONTEO FINAL DE ASIENTOS (EMPRESA 1): " . $stmt->fetchColumn() . "\n";
?>
