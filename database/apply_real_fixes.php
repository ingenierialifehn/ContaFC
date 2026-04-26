<?php
$ip = '172.18.0.3';
try {
    $db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Corrigiendo créditos negativos en Intereses Financiación...\n";
    $stmt = $db->prepare("UPDATE asientos 
                         SET credito = ABS(credito) 
                         WHERE (descripcion LIKE '%Intereses%' AND descripcion LIKE '%Financiaci%') 
                         AND credito < 0");
    $stmt->execute();
    $affected = $stmt->rowCount();
    echo "Filas actualizadas: $affected\n";

    echo "Corrigiendo naturalezas y tipos de cuenta en puc_cuentas...\n";
    $db->exec("UPDATE puc_cuentas SET naturaleza = 'D', tipo_cuenta = 'A' WHERE codigo LIKE '1%'");
    $db->exec("UPDATE puc_cuentas SET naturaleza = 'C', tipo_cuenta = 'P' WHERE codigo LIKE '2%'");
    $db->exec("UPDATE puc_cuentas SET naturaleza = 'C', tipo_cuenta = 'R' WHERE codigo LIKE '3%'");
    $db->exec("UPDATE puc_cuentas SET naturaleza = 'C', tipo_cuenta = 'R' WHERE codigo LIKE '4%'");
    $db->exec("UPDATE puc_cuentas SET naturaleza = 'D', tipo_cuenta = 'G' WHERE codigo LIKE '5%'");
    $db->exec("UPDATE puc_cuentas SET naturaleza = 'D', tipo_cuenta = 'G' WHERE codigo LIKE '6%'");
    $db->exec("UPDATE puc_cuentas SET naturaleza = 'D', tipo_cuenta = 'G' WHERE codigo LIKE '7%'");
    
    echo "Ajustes completados en servidor_db.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
