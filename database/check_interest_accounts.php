<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
$stmt = $db->query("SELECT p.codigo, p.nombre, COUNT(*) as total 
                   FROM asientos a 
                   JOIN puc_cuentas p ON a.cuenta_id = p.id 
                   WHERE (a.descripcion LIKE '%Intereses%' AND a.descripcion LIKE '%Financiaci%') 
                   GROUP BY p.codigo, p.nombre");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
