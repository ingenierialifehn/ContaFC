<?php
$jsonStr = file_get_contents(__DIR__ . '/dbf_data.json');
$dbfRows = json_decode($jsonStr, true);

// Crear un mapa de codigo -> nombre (basado en el DBF)
$accountNames = [];
foreach ($dbfRows as $row) {
    if (!isset($accountNames[$row['acct']]) || strlen($row['detalle']) > strlen($accountNames[$row['acct']])) {
        $accountNames[$row['acct']] = $row['detalle'];
    }
}

$ip = '172.18.0.3';
try {
    $db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Renombrando cuentas migradas en servidor_db...\n";
    $stmt = $db->query("SELECT id, codigo FROM puc_cuentas WHERE nombre LIKE 'Cuenta Migrada%'");
    $migrated = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($migrated as $acc) {
        $code = $acc['codigo'];
        if (isset($accountNames[$code])) {
            $newName = $accountNames[$code];
            $upd = $db->prepare("UPDATE puc_cuentas SET nombre = ? WHERE id = ?");
            $upd->execute([$newName, $acc['id']]);
            echo "Renombrada $code -> $newName\n";
        }
    }
    echo "Proceso finalizado.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
