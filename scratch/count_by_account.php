<?php
require __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();
$stmt = $db->prepare("SELECT SUM(debito - credito) FROM asientos a JOIN puc_cuentas p ON a.cuenta_id = p.id WHERE p.codigo = '11020101'");
$stmt->execute();
echo "Saldo Bco LAFISE (11020101): " . $stmt->fetchColumn() . "\n";
