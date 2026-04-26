<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
$stmt = $db->query("SELECT p.codigo, p.nombre, SUM(a.debito - a.credito) as saldo 
                   FROM asientos a 
                   JOIN puc_cuentas p ON a.cuenta_id = p.id 
                   WHERE p.nombre = 'Cuentas por cobrar Clientes' AND YEAR(a.fecha) <= 2023");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
