<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT COUNT(a.id) FROM asientos a JOIN comprobantes c ON a.comprobante_id = c.id WHERE c.estado = 'registrado'")->fetchColumn();
echo "Entries with registrado comprobante: $res\n";
