<?php
$jsonStr = file_get_contents(__DIR__ . '/dbf_data.json');
$dbfRows = json_decode($jsonStr, true);

$dbfDeb = 0;
$dbfCre = 0;
foreach ($dbfRows as $row) {
    if ($row['acct'] == '11050101' && date('Y', strtotime($row['fecha'])) == '2023') {
        $dbfDeb += $row['debito'];
        $dbfCre += $row['credito'];
    }
}
echo "DBF (2023): Debitos=$dbfDeb, Creditos=$dbfCre, Net=" . ($dbfDeb - $dbfCre) . "\n";

$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
$sql = "SELECT SUM(debito) as deb, SUM(credito) as cre FROM asientos 
        WHERE cuenta_id = (SELECT id FROM puc_cuentas WHERE codigo = '11050101') 
        AND YEAR(fecha) = 2023";
$res = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
echo "MySQL (2023): Debitos=" . $res['deb'] . ", Creditos=" . $res['cre'] . ", Net=" . ($res['deb'] - $res['cre']) . "\n";
