<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;charset=utf8mb4", 'root', 'root');
$dbs = $db->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);

foreach ($dbs as $dbName) {
    if (in_array($dbName, ['information_schema', 'mysql', 'performance_schema', 'sys'])) continue;
    try {
        $db->exec("USE `$dbName`");
        $stmt = $db->query("SELECT '$dbName' as db, a.empresa_id, p.codigo, p.nombre, SUM(a.debito - a.credito) as saldo 
                           FROM asientos a 
                           JOIN puc_cuentas p ON a.cuenta_id = p.id 
                           GROUP BY a.empresa_id, p.codigo, p.nombre 
                           HAVING ABS(saldo - 72351066.74) < 1000");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            echo "MATCH FOUND IN DB: $dbName\n";
            print_r($rows);
        }
    } catch (Exception $e) {}
}
