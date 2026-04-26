<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');

$sql = "SELECT SUM(credito) FROM asientos 
        WHERE cuenta_id = (SELECT id FROM puc_cuentas WHERE codigo = '11050101') 
        AND descripcion LIKE '%Intereses%'";
echo "Total intereses en 11050101: " . $db->query($sql)->fetchColumn() . "\n";
