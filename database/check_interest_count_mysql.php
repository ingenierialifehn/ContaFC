<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
echo "MySQL Interest Count (2023): " . $db->query("SELECT COUNT(*) FROM asientos WHERE (descripcion LIKE '%Intereses%' AND descripcion LIKE '%Financiaci%') AND YEAR(fecha) = 2023")->fetchColumn() . "\n";
