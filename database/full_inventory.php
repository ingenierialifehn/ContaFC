<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;charset=utf8mb4", 'root', 'root');
$dbs = $db->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);

foreach ($dbs as $dbName) {
    if (in_array($dbName, ['information_schema', 'mysql', 'performance_schema', 'sys'])) continue;
    echo "--- Database: $dbName ---\n";
    try {
        $db->exec("USE `$dbName`");
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $tbl) {
            $count = $db->query("SELECT COUNT(*) FROM `$tbl`")->fetchColumn();
            echo "  Table: $tbl ($count rows)\n";
            if ($tbl == 'asientos') {
                $firstId = $db->query("SELECT id FROM asientos ORDER BY id ASC LIMIT 1")->fetchColumn();
                $firstDesc = $db->query("SELECT descripcion FROM asientos ORDER BY id ASC LIMIT 1")->fetchColumn();
                echo "    First ID: $firstId, Desc: $firstDesc\n";
            }
        }
    } catch (Exception $e) { echo "  Error: " . $e->getMessage() . "\n"; }
}
