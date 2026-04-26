<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();
$eid = 1; // Assuming company 1 for now, but let's check all companies if possible

echo "COMPANIES:\n";
$companies = $db->query("SELECT id, nombre FROM empresas")->fetchAll(PDO::FETCH_ASSOC);
foreach ($companies as $c) {
    echo "ID: {$c['id']}, Name: {$c['nombre']}\n";
}

if (empty($companies)) {
    die("No companies found.\n");
}

$eid = $companies[0]['id'];
echo "\nChecking data for Company ID: $eid\n";

$comprobantesCount = $db->query("SELECT estado, COUNT(*) as cnt FROM comprobantes WHERE empresa_id = $eid GROUP BY estado")->fetchAll(PDO::FETCH_ASSOC);
echo "Comprobantes by state:\n";
foreach ($comprobantesCount as $row) {
    echo "State: {$row['estado']}, Count: {$row['cnt']}\n";
}

$asientosCount = $db->query("SELECT COUNT(*) FROM asientos WHERE empresa_id = $eid")->fetchColumn();
echo "Total Asientos: $asientosCount\n";

$asientosWithComp = $db->query("SELECT COUNT(*) FROM asientos a JOIN comprobantes c ON a.comprobante_id = c.id WHERE a.empresa_id = $eid AND c.estado = 'registrado'")->fetchColumn();
echo "Asientos with registered comprobante: $asientosWithComp\n";

$years = $db->query("SELECT DISTINCT YEAR(fecha) as anio FROM comprobantes WHERE empresa_id = $eid ORDER BY anio DESC")->fetchAll(PDO::FETCH_ASSOC);
echo "Years in comprobantes:\n";
foreach ($years as $y) {
    echo "Year: {$y['anio']}\n";
}

$pucCount = $db->query("SELECT COUNT(*) FROM puc_cuentas WHERE empresa_id = $eid AND activa = 1")->fetchColumn();
echo "Active PUC cuentas: $pucCount\n";

$pucTypes = $db->query("SELECT tipo_cuenta, COUNT(*) as cnt FROM puc_cuentas WHERE empresa_id = $eid GROUP BY tipo_cuenta")->fetchAll(PDO::FETCH_ASSOC);
echo "PUC accounts by type:\n";
foreach ($pucTypes as $row) {
    echo "Type: {$row['tipo_cuenta']}, Count: {$row['cnt']}\n";
}
