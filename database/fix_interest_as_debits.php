<?php
$ip = '172.18.0.3';
try {
    $db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Corrigiendo Intereses: Pasando de Créditos a Débitos para sumar al saldo...\n";

    // Seleccionamos los asientos que son de Intereses y que actualmente tienen crédito (que antes eran negativos)
    // Usamos la descripción para identificarlos como pidió el usuario
    $sql = "UPDATE asientos 
            SET debito = credito, credito = 0 
            WHERE (descripcion LIKE '%Intereses%' AND descripcion LIKE '%Financiaci%') 
            AND credito > 0 AND debito = 0";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $affected = $stmt->rowCount();
    
    echo "Movimientos de intereses ajustados: $affected\n";

    echo "Verificando nuevo balance de Cuentas por Cobrar (11050101)...\n";
    $sqlBalance = "SELECT SUM(a.debito - a.credito) 
                   FROM asientos a 
                   JOIN puc_cuentas p ON a.cuenta_id = p.id 
                   WHERE p.codigo = '11050101' AND YEAR(a.fecha) <= 2023";
    $finalBalance = $db->query($sqlBalance)->fetchColumn();
    echo "Nuevo Balance 11050101: " . number_format((float)$finalBalance, 2) . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
