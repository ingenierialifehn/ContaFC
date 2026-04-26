<?php
$mapping = [
    "312256640" => "Cuentas Por Cobrar Clientes",
    "329033856" => "Cuentas Por Cobrar Clientes",
    "873735810" => "Cuentas Por Cobrar Clientes",
    "902001026" => "Cuentas Por Cobrar Clientes",
    "903751811" => "Cuentas Por Cobrar Clientes",
    "904962691" => "Cuentas Por Cobrar Clientes",
    "915816577" => "Cuentas Por Cobrar Clientes",
    "917027457" => "Cuentas Por Cobrar Clientes",
    "918778242" => "Cuentas Por Cobrar Clientes",
    "935555458" => "Cuentas Por Cobrar Clientes",
    "949371009" => "Cuentas Por Cobrar Clientes",
    "950581889" => "Cuentas Por Cobrar Clientes",
    "952332674" => "Cuentas Por Cobrar Clientes",
    "1002664322" => "Cuentas Por Cobrar Clientes",
    "1021192323" => "Cuentas Por Cobrar Clientes",
    "1037969539" => "Cuentas Por Cobrar Clientes",
    "1050034305" => "Cuentas Por Cobrar Clientes",
    "1138632835" => "Cuentas Por Cobrar Clientes",
    "1222518915" => "Cuentas Por Cobrar Clientes",
    "1239296131" => "Cuentas Por Cobrar Clientes",
    "1256073347" => "Cuentas Por Cobrar Clientes",
    "1289627779" => "Cuentas Por Cobrar Clientes",
    "1457399939" => "Cuentas Por Cobrar Clientes",
    "1490954371" => "Cuentas Por Cobrar Clientes",
    "1558063235" => "Cuentas Por Cobrar Clientes",
    "1574840451" => "Caja General",
    "1641949315" => "Cuentas Por Cobrar Clientes",
    "1965045891" => "Cuentas Por Cobrar Clientes",
    "1977715841" => "BOFLOZA Occid 21-701-055679-3",
    "1980072322" => "Cuentas Por Cobrar Clientes"
];

try {
    $db = new PDO("mysql:host=172.18.0.3;dbname=contafc", "root", "root");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Corrigiendo nombres de cuentas (usando DESCRIPCION2 del Excel)...\n";
    $stmt = $db->prepare("UPDATE puc_cuentas SET nombre = ? WHERE codigo = ? AND empresa_id = 1");

    foreach ($mapping as $codigo => $nombre) {
        if ($nombre !== "No encontrado") {
            $stmt->execute([$nombre, $codigo]);
            echo "Corregido: $codigo -> $nombre\n";
        }
    }

    echo "\nProceso completado.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
