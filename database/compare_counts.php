<?php
$jsonStr = file_get_contents(__DIR__ . '/dbf_data.json');
$dbfRows = json_decode($jsonStr, true);

$dbfCount = 0;
foreach ($dbfRows as $row) {
    if ($row['acct'] == '11050101' && date('Y', strtotime($row['fecha'])) == '2023') {
        $dbfCount++;
    }
}

$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
$sql = "SELECT COUNT(*) FROM asientos WHERE cuenta_id = (SELECT id FROM puc_cuentas WHERE codigo = '11050101') AND YEAR(fecha) = 2023";
$mysqlCount = $db->query($sql)->fetchColumn();

echo "DBF Count for 11050101 in 2023: $dbfCount\n";
echo "MySQL Count for 11050101 in 2023: $mysqlCount\n";
