<?php
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();
$eid = 1;

$res = $db->query("SELECT nit_cc, count(*) as c FROM terceros WHERE empresa_id = $eid AND nit_cc != '' GROUP BY nit_cc HAVING c > 1");
echo "Duplicate RTN found:\n";
while($r = $res->fetch(PDO::FETCH_ASSOC)) {
    echo "RTN: {$r['nit_cc']} | Count: {$r['c']}\n";
    
    $ids = $db->prepare("SELECT id, codigo, razon_social FROM terceros WHERE nit_cc = :n AND empresa_id = $eid");
    $ids->execute([':n' => $r['nit_cc']]);
    while($row = $ids->fetch(PDO::FETCH_ASSOC)) {
        echo "  - ID: {$row['id']} | Cod: {$row['codigo']} | Name: {$row['razon_social']}\n";
    }
}
