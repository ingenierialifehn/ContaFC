<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "INICIANDO LIMPIEZA PROFUNDA DE DATOS DUPLICADOS (EMPRESA 1)...\n\n";

$accounts = [
    '11020104', '11020106', '11050130', '11060102', 
    '11060127', '12010106', '21010110', '27010101', '36100101'
];

try {
    $db->beginTransaction();

    foreach ($accounts as $code) {
        echo "Procesando cuenta: $code...\n";
        
        // Buscamos duplicados ignorando espacios en descripción y redondeando a 2 decimales
        $sql = "DELETE a1 FROM asientos a1
                JOIN puc_cuentas p1 ON a1.cuenta_id = p1.id
                INNER JOIN asientos a2 ON a1.comprobante_id = a2.comprobante_id
                    AND a1.cuenta_id = a2.cuenta_id
                    AND ROUND(a1.debito, 2) = ROUND(a2.debito, 2)
                    AND ROUND(a1.credito, 2) = ROUND(a2.credito, 2)
                    AND a1.fecha = a2.fecha
                    AND TRIM(a1.descripcion) = TRIM(a2.descripcion)
                WHERE p1.codigo = :code 
                  AND p1.empresa_id = 1
                  AND a1.id > a2.id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':code' => $code]);
        $deleted = $stmt->rowCount();
        echo "  -> Eliminados: $deleted\n";
    }

    // Caso especial CxC Clientes (11050101) - El abono de 14M
    $sqlCxC = "DELETE a FROM asientos a
               JOIN puc_cuentas p ON a.cuenta_id = p.id
               WHERE p.codigo = '11050101' 
                 AND p.empresa_id = 1 
                 AND a.credito > 14000000";
    $deletedCxC = $db->exec($sqlCxC);
    echo "\nAjuste especial CxC Clientes (14M) eliminado: $deletedCxC\n";

    $db->commit();
    echo "\nLIMPIEZA COMPLETADA.\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
