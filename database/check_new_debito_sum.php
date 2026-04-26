<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
echo "Suma de intereses pasados a debito: " . $db->query("SELECT SUM(debito) FROM asientos WHERE (descripcion LIKE '%Intereses%' AND descripcion LIKE '%Financiaci%') AND credito = 0")->fetchColumn() . "\n";
