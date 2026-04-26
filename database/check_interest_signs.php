<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
$stmt = $db->query("SELECT credito, COUNT(*) as qty 
                   FROM asientos 
                   WHERE (descripcion LIKE '%Intereses%' AND descripcion LIKE '%Financiaci%') 
                   GROUP BY (credito < 0)");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
