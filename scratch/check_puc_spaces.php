<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();
$eid = 1;

$puc = $db->query("SELECT id, codigo FROM puc_cuentas WHERE empresa_id = $eid LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
echo "PUC CODES INSPECTION:\n";
foreach ($puc as $p) {
    echo "ID: {$p['id']}, Code: '{$p['codigo']}', Len: " . strlen($p['codigo']) . "\n";
}

$spaces = $db->query("SELECT COUNT(*) FROM puc_cuentas WHERE codigo LIKE ' %' OR codigo LIKE '% '")->fetchColumn();
echo "Codes with leading/trailing spaces: $spaces\n";
