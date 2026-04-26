<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');

// Buscamos comprobantes que tengan la marca de ajuste automático que hice
$sql = "SELECT SUM(credito) FROM asientos WHERE observaciones LIKE '%Ajuste automático%'";
// Espera, no puse observaciones en los asientos, solo en los comprobantes.
// Busco asientos en comprobantes con esa observación.

$sql = "SELECT SUM(a.credito) 
        FROM asientos a 
        JOIN comprobantes c ON a.comprobante_id = c.id 
        WHERE c.observaciones LIKE '%Ajuste automático%' 
        AND a.descripcion LIKE '%Intereses%'";
echo "Suma de intereses ajustados: " . $db->query($sql)->fetchColumn() . "\n";
