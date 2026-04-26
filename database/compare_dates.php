<?php
$jsonStr = file_get_contents(__DIR__ . '/dbf_data.json');
$dbfRows = json_decode($jsonStr, true);

$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
$stmt = $db->query("SELECT a.id, p.codigo, a.debito, a.credito, a.fecha 
                   FROM asientos a 
                   JOIN puc_cuentas p ON a.cuenta_id = p.id 
                   ORDER BY a.id ASC LIMIT 10");
$sqlRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

for ($i = 0; $i < 10; $i++) {
    echo "ID " . $sqlRows[$i]['id'] . ": DBF Date=" . $dbfRows[$i]['fecha'] . ", SQL Date=" . $sqlRows[$i]['fecha'] . "\n";
}
