<?php
$ip = '172.26.0.2';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'contafc_user', 'C0nt4FC!2026');
$stmt = $db->query("SELECT p.codigo, p.nombre, SUM(a.debito - a.credito) as saldo 
                   FROM asientos a 
                   JOIN puc_cuentas p ON a.cuenta_id = p.id 
                   GROUP BY p.codigo, p.nombre 
                   HAVING ABS(saldo - 72351066.74) < 1000");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
