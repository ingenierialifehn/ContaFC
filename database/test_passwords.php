<?php
$passwords = ['', 'R00tS3cur3!', 'C0nt4FC!2026'];
foreach ($passwords as $pwd) {
    try {
        $dsn = "mysql:host=host.docker.internal;port=3389;dbname=contafc;charset=utf8mb4";
        $pdo = new PDO($dsn, 'root', $pwd);
        $count = $pdo->query("SELECT COUNT(*) FROM asientos")->fetchColumn();
        echo "Successfully connected with password '$pwd'! Count: $count\n";
        exit;
    } catch (Exception $e) {
        echo "Failed with '$pwd': " . $e->getMessage() . "\n";
    }
}
