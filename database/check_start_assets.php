<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
$stmt = $db->query("SELECT id, codigo, nombre FROM puc_cuentas WHERE codigo LIKE '1%' ORDER BY codigo ASC LIMIT 20");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
