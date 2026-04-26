<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');

// Simular OfficialBookService::getComparativeBalance (parte relevante)
$year = 2023;
$prevYear = 2022;

$sql = "SELECT SUM(debito - credito) FROM asientos WHERE cuenta_id IN (SELECT id FROM puc_cuentas WHERE codigo LIKE '2%') AND YEAR(fecha) <= 2023";
$saldoRaw = (float)$db->query($sql)->fetchColumn();

// Naturaleza C -> invertimos
$saldoInvertido = $saldoRaw * -1;

echo "Saldo Raw Pasivos 2023: $saldoRaw\n";
echo "Saldo Invertido (Reporte): $saldoInvertido\n";
echo "Formateado: " . number_format($saldoInvertido, 2) . "\n";
