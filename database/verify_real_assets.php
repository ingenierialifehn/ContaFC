<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
$stmt = $db->query("SELECT SUM(a.debito - a.credito) 
                   FROM asientos a 
                   JOIN puc_cuentas p ON a.cuenta_id = p.id 
                   WHERE p.codigo LIKE '1%' AND YEAR(a.fecha) <= 2023");
echo "Total Real Assets (<= 2023): " . $stmt->fetchColumn() . "\n";
