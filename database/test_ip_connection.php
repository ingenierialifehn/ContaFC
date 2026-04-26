<?php
$ip = '172.18.0.3';
try {
    $dsn = "mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '');
    $count = $pdo->query("SELECT COUNT(*) FROM asientos")->fetchColumn();
    echo "Connected to $ip! Count: $count\n";
} catch (Exception $e) {
    echo "Failed to connect to $ip: " . $e->getMessage() . "\n";
}
