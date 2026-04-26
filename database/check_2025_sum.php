<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');

echo "Verificando Activos 2025...\n";
$sql = "SELECT SUM(a.debito - a.credito) 
        FROM asientos a 
        JOIN puc_cuentas p ON a.cuenta_id = p.id 
        JOIN comprobantes c ON a.comprobante_id = c.id
        WHERE p.codigo LIKE '1%' AND YEAR(c.fecha) <= 2025 AND c.estado = 'registrado'";
$total = $db->query($sql)->fetchColumn();
echo "Total Activos (acumulado a 2025): " . number_format($total, 2) . "\n";

echo "\nDesglose por año:\n";
for($y=2022; $y<=2025; $y++) {
    $s = $db->query("SELECT SUM(a.debito - a.credito) FROM asientos a JOIN comprobantes c ON a.comprobante_id = c.id JOIN puc_cuentas p ON a.cuenta_id = p.id WHERE p.codigo LIKE '1%' AND YEAR(c.fecha) = $y AND c.estado = 'registrado'")->fetchColumn();
    echo "$y: " . number_format($s, 2) . "\n";
}
