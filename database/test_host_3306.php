<?php
try {
    $dsn = "mysql:host=host.docker.internal;port=3306;dbname=contafc;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '');
    echo "Connected to HOST 3306. Count: " . $pdo->query("SELECT COUNT(*) FROM asientos")->fetchColumn() . "\n";
} catch (Exception $e) {
    echo "Host 3306 failed: " . $e->getMessage() . "\n";
}
