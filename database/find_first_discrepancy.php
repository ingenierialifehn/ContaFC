<?php
$jsonStr = file_get_contents(__DIR__ . '/dbf_data.json');
$dbfRows = json_decode($jsonStr, true);
$dbfData = [];
foreach ($dbfRows as $row) {
    if ($row['acct'] == '11050101' && date('Y', strtotime($row['fecha'])) == '2023') {
        $dbfData[] = $row;
    }
}

$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
$mysqlData = $db->query("SELECT debito, credito, descripcion, fecha FROM asientos 
                         WHERE cuenta_id = (SELECT id FROM puc_cuentas WHERE codigo = '11050101') 
                         AND YEAR(fecha) = 2023 
                         ORDER BY fecha ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

echo "Buscando la primera discrepancia...\n";
for ($i = 0; $i < min(count($dbfData), count($mysqlData)); $i++) {
    $d1 = (float)$dbfData[$i]['debito'];
    $c1 = (float)$dbfData[$i]['credito'];
    $d2 = (float)$mysqlData[$i]['debito'];
    $c2 = (float)$mysqlData[$i]['credito'];
    
    if (abs($d1 - $d2) > 0.01 || abs($c1 - $c2) > 0.01) {
        echo "Match Fail at Row $i:\n";
        echo "  DBF:   Date=" . $dbfData[$i]['fecha'] . ", Deb=$d1, Cre=$c1, Desc=" . $dbfData[$i]['detalle'] . "\n";
        echo "  MySQL: Date=" . $mysqlData[$i]['fecha'] . ", Deb=$d2, Cre=$c2, Desc=" . $mysqlData[$i]['descripcion'] . "\n";
        break;
    }
}
