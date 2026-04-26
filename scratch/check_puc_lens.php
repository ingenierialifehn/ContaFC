<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();
$eid = 1;

$puc = $db->query("SELECT codigo, nombre, nivel, LENGTH(codigo) as len FROM puc_cuentas WHERE empresa_id = $eid ORDER BY codigo LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
echo "PUC SAMPLES:\n";
foreach ($puc as $p) {
    echo "Code: {$p['codigo']}, Name: {$p['nombre']}, Level: {$p['nivel']}, Len: {$p['len']}\n";
}

$lenDist = $db->query("SELECT LENGTH(codigo) as len, COUNT(*) as cnt FROM puc_cuentas WHERE empresa_id = $eid GROUP BY len")->fetchAll(PDO::FETCH_ASSOC);
echo "\nLength Distribution:\n";
foreach ($lenDist as $row) {
    echo "Len: {$row['len']}, Count: {$row['cnt']}\n";
}
