<?php
try {
    $db = new PDO("mysql:host=172.18.0.3;dbname=contafc", "root", "root");
    $stmt = $db->query("SELECT COUNT(*) FROM puc_cuentas WHERE created_at = '2026-04-05 11:54:24'");
    echo $stmt->fetchColumn();
} catch (Exception $e) {
    echo $e->getMessage();
}
