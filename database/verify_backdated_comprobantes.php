<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');

echo "Buscando asientos con fecha > 2023 que están en comprobantes de <= 2023...\n";
$sql = "SELECT a.id, a.fecha as fecha_asiento, c.fecha as fecha_comprobante, a.debito, a.credito 
        FROM asientos a 
        JOIN comprobantes c ON a.comprobante_id = c.id 
        WHERE YEAR(a.fecha) > 2023 AND YEAR(c.fecha) <= 2023 
        LIMIT 10";
print_r($db->query($sql)->fetchAll(PDO::FETCH_ASSOC));
