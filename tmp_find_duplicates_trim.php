<?php
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();
$eid = 1;

echo "Checking for duplicates with TRIM()...\n";
$res = $db->query("SELECT TRIM(razon_social) as r, count(*) as c FROM terceros WHERE empresa_id = $eid GROUP BY r HAVING c > 1");
while($r = $res->fetch(PDO::FETCH_ASSOC)) {
    echo "Similar Name: '{$r['r']}' | Count: {$r['c']}\n";
    $stmt = $db->prepare("SELECT id, razon_social, nit_cc FROM terceros WHERE TRIM(razon_social) = :n AND empresa_id = $eid");
    $stmt->execute([':n' => $r['r']]);
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  - ID: {$row['id']} | Original: '{$row['razon_social']}' | RTN: {$row['nit_cc']}\n";
    }
}
