<?php
$timestamp = '2026-04-05 11:54:24';
$db = new PDO("mysql:host=172.18.0.3;dbname=contafc", "root", "root");

$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    $columns = $db->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('created_at', $columns)) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM $table WHERE created_at = ?");
        $stmt->execute([$timestamp]);
        $count = $stmt->fetchColumn();
        if ($count > 0) {
            echo "Tabla '$table' tiene $count registros creados en $timestamp\n";
            // Mostrar un ejemplo si no es puc_cuentas
            if ($table != 'puc_cuentas') {
                $stmt = $db->prepare("SELECT * FROM $table WHERE created_at = ? LIMIT 1");
                $stmt->execute([$timestamp]);
                print_r($stmt->fetch(PDO::FETCH_ASSOC));
            }
        }
    }
}
