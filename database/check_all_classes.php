<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');

echo "Resumen de saldos por Clase (Toda la historia):\n";
$sql = "SELECT SUBSTR(p.codigo, 1, 1) as clase, SUM(a.debito - a.credito) as saldo
        FROM asientos a
        JOIN puc_cuentas p ON a.cuenta_id = p.id
        GROUP BY clase
        ORDER BY clase";
$res = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

foreach ($res as $row) {
    echo "Clase " . $row['clase'] . ": " . number_format($row['saldo'], 2) . "\n";
}
