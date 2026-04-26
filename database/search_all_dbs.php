<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;charset=utf8mb4", 'root', 'root');
$dbs = $db->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($dbs as $dbName) {
    try {
        $db->exec("USE `$dbName`");
        $stmt = $db->query("SELECT '$dbName' as db, id, codigo, nombre FROM puc_cuentas WHERE codigo LIKE '%11030121%'");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            print_r($rows);
        }
    } catch (Exception $e) {}
}
