<?php
try {
    $db = new PDO("mysql:host=172.18.0.3;dbname=contafc", "root", "root");
    $stmt = $db->query("SELECT MIN(id), MAX(id) FROM terceros WHERE created_at = '2026-04-05 11:54:24'");
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo $e->getMessage();
}
