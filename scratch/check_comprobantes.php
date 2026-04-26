<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();
$eid = 1;

$comps = $db->query("SELECT id, numero, fecha, estado FROM comprobantes WHERE empresa_id = $eid ORDER BY fecha DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
echo "LATEST COMPROBANTES:\n";
foreach ($comps as $c) {
    echo "ID: {$c['id']}, Num: {$c['numero']}, Date: {$c['fecha']}, State: {$c['estado']}\n";
}

$counts = $db->query("SELECT YEAR(fecha) as anio, COUNT(*) as cnt FROM comprobantes WHERE empresa_id = $eid GROUP BY anio")->fetchAll(PDO::FETCH_ASSOC);
echo "\nCounts by Year:\n";
foreach ($counts as $row) {
    echo "Year: {$row['anio']}, Count: {$row['cnt']}\n";
}
