<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');

// Datos del sistema antiguo (según las fotos)
$legacy = [
    '11020104' => 17830,
    '11020106' => 126163,
    '11050101' => 1147234,
    '11050130' => 300000,
    '11400104' => 5000,
    '11500103' => 5200,
    '11600102' => 5000,
    '11600127' => 68000,
    '12010106' => 34204333,
    '21010110' => 3739742,
    '27010101' => 31085333,
    '36100101' => 257445,
];

echo "Comparativa de saldos (MySQL vs Legacy):\n";
echo str_pad("Codigo", 12) . " | " . str_pad("MySQL", 15) . " | " . str_pad("Legacy", 15) . " | Diff\n";

foreach ($legacy as $code => $val) {
    $sql = "SELECT SUM(a.debito - a.credito) 
            FROM asientos a 
            JOIN puc_cuentas p ON a.cuenta_id = p.id 
            WHERE p.codigo = '$code' AND YEAR(a.fecha) <= 2023";
    $mysqlVal = (float)$db->query($sql)->fetchColumn();
    $diff = $mysqlVal - $val;
    echo str_pad($code, 12) . " | " . str_pad(number_format($mysqlVal, 2), 15) . " | " . str_pad(number_format($val, 2), 15) . " | " . number_format($diff, 2) . "\n";
}
