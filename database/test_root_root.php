<?php
$ip = '172.18.0.3';
try {
    $dsn = "mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', 'root');
    $count = $pdo->query("SELECT COUNT(*) FROM asientos")->fetchColumn();
    echo "Connected to $ip with password 'root'! Count: $count\n";
} catch (Exception $e) {
    echo "Failed to connect to $ip with password 'root': " . $e->getMessage() . "\n";
}
