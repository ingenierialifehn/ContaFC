<?php
$search = ['1002664322', '1021192323', '1037969539', '1050034305'];
$db = new PDO("mysql:host=172.18.0.3;dbname=contafc", "root", "root");

$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    if ($table == 'puc_cuentas') continue; // Ya sabemos que están aquí
    $columns = $db->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($columns as $column) {
        foreach ($search as $s) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM $table WHERE `$column` LIKE ?");
            $stmt->execute(["%$s%"]);
            if ($stmt->fetchColumn() > 0) {
                echo "Encontrado '$s' en tabla '$table', columna '$column'\n";
            }
        }
    }
}
