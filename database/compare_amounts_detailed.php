<?php
$jsonStr = file_get_contents(__DIR__ . '/dbf_data.json');
$dbfRows = json_decode($jsonStr, true);

// Filtrar DBF para 11050101 en 2023
$dbfData = [];
foreach ($dbfRows as $row) {
    if ($row['acct'] == '11050101' && date('Y', strtotime($row['fecha'])) == '2023') {
        $dbfData[] = $row;
    }
}

$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
$sql = "SELECT debito, credito, descripcion FROM asientos 
        WHERE cuenta_id = (SELECT id FROM puc_cuentas WHERE codigo = '11050101') 
        AND YEAR(fecha) = 2023 
        ORDER BY id ASC";
$mysqlData = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

echo "Comparando montos para 11050101...\n";
$diffSum = 0;
for ($i = 0; $i < count($dbfData); $i++) {
    $d1 = (float)$dbfData[$i]['debito'];
    $c1 = (float)$dbfData[$i]['credito'];
    
    // El sistema viejo probablemente sumaba el crédito si era negativo
    $net1 = $d1 - $c1;
    
    $d2 = (float)$mysqlData[$i]['debito'];
    $c2 = (float)$mysqlData[$i]['credito'];
    $net2 = $d2 - $c2;
    
    if (abs($net1 - $net2) > 0.01) {
        echo "Row $i mismatch: DBF Net=$net1 ($d1 - $c1), MySQL Net=$net2 ($d2 - $c2). Desc: " . $dbfData[$i]['detalle'] . "\n";
        $diffSum += ($net1 - $net2);
    }
}
echo "Total Diff Sum: $diffSum\n";
