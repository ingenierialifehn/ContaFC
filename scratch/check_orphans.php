<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT COUNT(*) FROM asientos a LEFT JOIN comprobantes c ON a.comprobante_id = c.id WHERE c.id IS NULL")->fetchColumn();
echo "Orphaned entries: $res\n";
