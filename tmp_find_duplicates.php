<?php
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();
$eid = 1;

$res = $db->query("SELECT razon_social, count(*) as c FROM terceros WHERE empresa_id = $eid GROUP BY razon_social HAVING c > 1");
echo "Duplicate names found:\n";
while($r = $res->fetch(PDO::FETCH_ASSOC)) {
    echo "Name: {$r['razon_social']} | Count: {$r['c']}\n";
    
    // List IDs for this name
    $ids = $db->prepare("SELECT id, codigo, nit_cc FROM terceros WHERE razon_social = :n AND empresa_id = $eid");
    $ids->execute([':n' => $r['razon_social']]);
    while($row = $ids->fetch(PDO::FETCH_ASSOC)) {
        echo "  - ID: {$row['id']} | Cod: {$row['codigo']} | RTN: {$row['nit_cc']}\n";
    }
}
