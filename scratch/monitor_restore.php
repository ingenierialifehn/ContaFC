<?php
require __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();
$count = $db->query("SELECT COUNT(*) FROM asientos WHERE comprobante_id = 9999")->fetchColumn();
echo "Registros en 9999: $count\n";
