<?php
try {
    // Intentamos conectar al servidor_db a través del host (puerto 3389)
    $dsn = "mysql:host=host.docker.internal;port=3389;dbname=contafc;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '');
    $count = $pdo->query("SELECT COUNT(*) FROM asientos")->fetchColumn();
    echo "Successfully connected to servidor_db! Count: $count\n";
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
