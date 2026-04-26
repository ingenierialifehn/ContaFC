<?php
$configs = [
    ['host' => '127.0.0.1', 'port' => 3306],
    ['host' => '127.0.0.1', 'port' => 3307],
];

foreach ($configs as $cfg) {
    try {
        $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname=contafc;charset=utf8mb4";
        $pdo = new PDO($dsn, 'root', 'R00tS3cur3!');
        $count = $pdo->query("SELECT COUNT(*) FROM asientos")->fetchColumn();
        echo "DB at {$cfg['host']}:{$cfg['port']} has $count rows in asientos.\n";
    } catch (Exception $e) {
        echo "Could not connect to {$cfg['host']}:{$cfg['port']} : " . $e->getMessage() . "\n";
    }
}
