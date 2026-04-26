<?php
// No bootstrap here to avoid override
$host = '172.18.0.4';
$user = 'root';
$pass = 'R00tS3cur3!'; // Try common password or from docker-compose
try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $stmt = $pdo->query("SHOW DATABASES");
    echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN), JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo $e->getMessage();
}
