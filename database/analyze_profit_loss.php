<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');

echo "Analizando Ingresos y Gastos 2023...\n";

$sql = "SELECT SUBSTR(p.codigo, 1, 1) as clase, SUM(a.debito - a.credito) as saldo
        FROM asientos a
        JOIN puc_cuentas p ON a.cuenta_id = p.id
        WHERE YEAR(a.fecha) = 2023
        GROUP BY clase
        ORDER BY clase";

$res = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$balance = [];
foreach ($res as $row) {
    $balance[$row['clase']] = (float)$row['saldo'];
    echo "Clase " . $row['clase'] . ": " . number_format($row['saldo'], 2) . "\n";
}

$activos = $balance['1'] ?? 0;
$pasivos = abs($balance['2'] ?? 0);
$patrimonio = abs($balance['3'] ?? 0);
$ingresos = abs($balance['4'] ?? 0);
$gastos = ($balance['5'] ?? 0) + ($balance['6'] ?? 0);

$utilidad = $ingresos - $gastos;

echo "\n--- Resumen 2023 ---\n";
echo "Total Activos: " . number_format($activos, 2) . "\n";
echo "Total Pasivos: " . number_format($pasivos, 2) . "\n";
echo "Total Patrimonio (Capital): " . number_format($patrimonio, 2) . "\n";
echo "Ingresos: " . number_format($ingresos, 2) . "\n";
echo "Gastos: " . number_format($gastos, 2) . "\n";
echo "Utilidad/Pérdida del periodo: " . number_format($utilidad, 2) . "\n";

$totalPasPat = $pasivos + $patrimonio + $utilidad;
echo "Total Pasivo + Patrimonio (con Utilidad): " . number_format($totalPasPat, 2) . "\n";
echo "Diferencia Final: " . number_format($activos - $totalPasPat, 2) . "\n";
