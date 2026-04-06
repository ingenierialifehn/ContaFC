<?php
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Database;
use ContaFC\Core\Auth;

header('Content-Type: text/plain');

$db = Database::getInstance()->getPdo();
$eid = Auth::empresaId();

echo "Empresa ID in session: $eid\n";

$res = $db->query("SELECT id, nombre FROM empresas");
echo "\nAvailable empresas:\n";
while($r = $res->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$r['id']} | Name: {$r['nombre']}\n";
}

$count = $db->query("SELECT count(*) FROM terceros")->fetchColumn();
echo "\nTotal terceros in DB: $count\n";

$res = $db->query("SELECT id, empresa_id, codigo, razon_social, nit_cc, tipo_tercero FROM terceros LIMIT 20");
echo "\nSample terceros:\n";
while($r = $res->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$r['id']} | EID: {$r['empresa_id']} | Cod: {$r['codigo']} | Name: {$r['razon_social']} | RTN: {$r['nit_cc']} | Type: {$r['tipo_tercero']}\n";
}
