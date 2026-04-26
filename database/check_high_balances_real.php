<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
$sql = "SELECT a.cuenta_id, p.codigo, p.nombre, SUM(a.debito - a.credito) as saldo 
        FROM asientos a 
        JOIN puc_cuentas p ON a.cuenta_id = p.id 
        WHERE YEAR(a.fecha) <= 2023 
        GROUP BY a.cuenta_id, p.codigo, p.nombre 
        HAVING ABS(saldo) > 1000000 
        ORDER BY ABS(saldo) DESC";
print_r($db->query($sql)->fetchAll(PDO::FETCH_ASSOC));
