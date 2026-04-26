<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
echo "Asientos without valid comprobante_id: " . $db->query("SELECT COUNT(*) FROM asientos WHERE comprobante_id IS NULL OR comprobante_id = 0")->fetchColumn() . "\n";
echo "Asientos with comprobante_id that does not exist: " . $db->query("SELECT COUNT(*) FROM asientos a LEFT JOIN comprobantes c ON a.comprobante_id = c.id WHERE c.id IS NULL AND a.comprobante_id IS NOT NULL")->fetchColumn() . "\n";
