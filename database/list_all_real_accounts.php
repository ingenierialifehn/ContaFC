<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
$stmt = $db->query("SELECT id, codigo, nombre FROM puc_cuentas ORDER BY codigo ASC");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
