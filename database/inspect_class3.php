<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');

echo "Inspeccionando registros de Patrimonio (Clase 3)...\n";
$sql = "SELECT a.id, a.fecha, p.codigo, p.nombre, a.debito, a.credito 
        FROM asientos a 
        JOIN puc_cuentas p ON a.cuenta_id = p.id 
        WHERE SUBSTR(p.codigo, 1, 1) = '3' 
        ORDER BY a.fecha ASC 
        LIMIT 20";
print_r($db->query($sql)->fetchAll(PDO::FETCH_ASSOC));
