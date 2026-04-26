<?php
$ip = '172.18.0.3';
try {
    $db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Sincronizando fechas de comprobantes con sus asientos...\n";
    
    // Usamos una tabla temporal para calcular las fechas correctas (la fecha mínima de sus asientos)
    $db->exec("CREATE TEMPORARY TABLE tmp_fechas_comp AS 
               SELECT comprobante_id, MIN(fecha) as nueva_fecha 
               FROM asientos 
               WHERE comprobante_id IS NOT NULL AND comprobante_id <> 0
               GROUP BY comprobante_id");

    $stmt = $db->prepare("UPDATE comprobantes c
                         JOIN tmp_fechas_comp t ON c.id = t.comprobante_id
                         SET c.fecha = t.nueva_fecha
                         WHERE c.fecha <> t.nueva_fecha");
    
    $stmt->execute();
    $affected = $stmt->rowCount();
    
    echo "Comprobantes actualizados: $affected\n";
    
    // Limpieza
    $db->exec("DROP TEMPORARY TABLE tmp_fechas_comp");

    echo "Verificando nuevo balance de Activos 2023...\n";
    $sqlBalance = "SELECT SUM(a.debito - a.credito) 
                   FROM asientos a 
                   JOIN puc_cuentas p ON a.cuenta_id = p.id 
                   JOIN comprobantes c ON a.comprobante_id = c.id
                   WHERE p.codigo LIKE '1%' AND YEAR(c.fecha) <= 2023";
    $finalBalance = $db->query($sqlBalance)->fetchColumn();
    echo "Nuevo Balance Activos 2023: " . number_format((float)$finalBalance, 2) . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
