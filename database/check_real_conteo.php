<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
$stmt = $db->query("SELECT COUNT(*) FROM asientos WHERE conteo IS NOT NULL");
echo "Rows with conteo: " . $stmt->fetchColumn() . "\n";
$stmt = $db->query("SELECT id, conteo, debito, credito, descripcion FROM asientos LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
