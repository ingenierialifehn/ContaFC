<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');

echo "Saldos de Clase 3 por Año:\n";
$sql = "SELECT YEAR(a.fecha) as anio, SUM(a.debito - a.credito) as saldo
        FROM asientos a
        JOIN puc_cuentas p ON a.cuenta_id = p.id
        WHERE SUBSTR(p.codigo, 1, 1) = '3'
        GROUP BY anio
        ORDER BY anio";
$res = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

foreach ($res as $row) {
    echo "Año " . $row['anio'] . ": " . number_format($row['saldo'], 2) . "\n";
}
