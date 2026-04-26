<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
print_r($db->query("SELECT codigo, nombre, naturaleza FROM puc_cuentas WHERE codigo LIKE '2%' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC));
