<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');

echo "Buscando registros 'futuros' metidos en comprobantes de 2023...\n";
$sql = "SELECT a.id, a.fecha as fecha_asiento, c.fecha as fecha_comprobante, p.codigo, a.debito, a.credito 
        FROM asientos a 
        JOIN comprobantes c ON a.comprobante_id = c.id 
        JOIN puc_cuentas p ON a.cuenta_id = p.id
        WHERE YEAR(a.fecha) > 2023 AND YEAR(c.fecha) <= 2023 
        LIMIT 20";
print_r($db->query($sql)->fetchAll(PDO::FETCH_ASSOC));
