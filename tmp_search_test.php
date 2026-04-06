<?php
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Database;
use ContaFC\Core\Auth;

header('Content-Type: text/plain');

$db = Database::getInstance()->getPdo();
$eid = 1; // From previous dump

function test_search($q, $tipo = '') {
    global $db, $eid;
    echo "Testing search for q='$q', tipo='$tipo'\n";
    
    $sql = "SELECT id, codigo, nit_cc, razon_social, tipo_tercero 
            FROM terceros 
            WHERE empresa_id = :eid";
    $params = [':eid' => $eid];

    if ($tipo) {
        $sql .= " AND (tipo_tercero LIKE :tipo OR tipo_tercero IS NULL OR tipo_tercero = '')";
        $params[':tipo'] = "%$tipo%";
    }

    if ($q !== '') {
        $sql .= " AND (LOWER(codigo) LIKE :q OR nit_cc LIKE :q OR LOWER(razon_social) LIKE :q)";
        $params[':q'] = "%" . strtolower($q) . "%";
    }

    $sql .= " ORDER BY razon_social ASC LIMIT 50";
    
    echo "SQL: $sql\n";
    echo "Params: " . json_encode($params) . "\n";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Results count: " . count($data) . "\n";
    foreach($data as $r) {
        echo "- ID: {$r['id']} | Nam: {$r['razon_social']} | Type: {$r['tipo_tercero']}\n";
    }
    echo "-----------------------------------\n";
}

test_search('wineroqui');
test_search('WINEROQUI');
test_search('', 'cliente');
test_search('wineroqui', 'cliente');
test_search('800111222'); // partial RTN
