<?php
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();

echo "Checking for duplicates across ALL companies...\n";
$res = $db->query("SELECT razon_social, count(*) as c FROM terceros GROUP BY razon_social HAVING c > 1");
while($r = $res->fetch(PDO::FETCH_ASSOC)) {
    echo "Name: '{$r['razon_social']}' | Count: {$r['c']}\n";
    $stmt = $db->prepare("SELECT id, empresa_id, nit_cc FROM terceros WHERE razon_social = :n");
    $stmt->execute([':n' => $r['razon_social']]);
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  - ID: {$row['id']} | EID: {$row['empresa_id']} | RTN: {$row['nit_cc']}\n";
    }
}
