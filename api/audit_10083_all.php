<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "COMPARING ALL ENTRIES FOR 10083\n";
$stmt = $db->query("SELECT id, debito, credito, created_at FROM asientos WHERE comprobante_id = 10083 AND cuenta_id = 85");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo "ID: {$row['id']} | Amount: " . ($row['debito'] - $row['credito']) . " | Created: {$row['created_at']}\n";
}
?>
