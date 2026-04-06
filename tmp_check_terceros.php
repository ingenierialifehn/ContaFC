<?php
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Database;
use ContaFC\Core\Auth;

// Simular login if needed or just get DB
$db = Database::getInstance()->getPdo();
$eid = 1; // Assuming 1 for testing or let's find out available empresas

echo "Empresa ID: $eid\n";

$stmt = $db->query("SELECT id, codigo, nit_cc, razon_social, tipo_tercero FROM terceros LIMIT 10");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Terceros found: " . count($rows) . "\n";
foreach($rows as $r) {
    echo "ID: {$r['id']} | Cod: {$r['codigo']} | RTN: {$r['nit_cc']} | Name: {$r['razon_social']} | Type: {$r['tipo_tercero']}\n";
}

// Test a search similar to the API
$q = 'a'; // common letter
$params = [':eid' => $eid, ':q' => "%$q%"];
$sql = "SELECT id, razon_social FROM terceros WHERE empresa_id = :eid AND (LOWER(codigo) LIKE :q OR nit_cc LIKE :q OR LOWER(razon_social) LIKE :q)";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\nSearch for '$q' results: " . count($results) . "\n";
foreach($results as $r) {
    echo "- {$r['razon_social']}\n";
}
