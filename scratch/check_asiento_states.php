<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();
$eid = 1;

$states = $db->query("SELECT c.estado, COUNT(*) as cnt FROM asientos a JOIN comprobantes c ON a.comprobante_id = c.id WHERE a.empresa_id = $eid GROUP BY c.estado")->fetchAll(PDO::FETCH_ASSOC);
echo "Asientos by Comprobante State:\n";
foreach ($states as $row) {
    echo "State: {$row['estado']}, Count: {$row['cnt']}\n";
}
