<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("SELECT COUNT(*) FROM asientos WHERE cuenta_id = (SELECT id FROM puc_cuentas WHERE codigo = '11050101')");
echo "MySQL records for 11050101: " . $stmt->fetchColumn() . "\n";
