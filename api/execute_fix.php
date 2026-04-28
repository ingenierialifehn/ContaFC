<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "INICIANDO LIMPIEZA DE DATOS DUPLICADOS (EMPRESA 1)...\n\n";

try {
    $db->beginTransaction();

    // 1. Limpiar duplicados exactos en asientos (mismo comprobante, cuenta, debito, credito, descripcion, fecha)
    // Mantener solo el ID más bajo de cada grupo
    $sqlDelete = "DELETE a1 FROM asientos a1
                  INNER JOIN asientos a2 ON a1.comprobante_id = a2.comprobante_id
                    AND a1.cuenta_id = a2.cuenta_id
                    AND a1.debito = a2.debito
                    AND a1.credito = a2.credito
                    AND a1.fecha = a2.fecha
                    AND a1.descripcion = a2.descripcion
                  WHERE a1.id > a2.id 
                    AND a1.empresa_id = 1";
    
    $deleted = $db->exec($sqlDelete);
    echo "Registros duplicados exactos eliminados: $deleted\n";

    // 2. Caso específico CxC Clientes (Eliminar el abono de 14M que es un error de migración)
    $sqlCxC = "DELETE a FROM asientos a
               JOIN puc_cuentas p ON a.cuenta_id = p.id
               WHERE p.codigo = '11050101' 
                 AND p.empresa_id = 1 
                 AND a.credito > 14000000 
                 AND a.descripcion LIKE '%SALDO SEGUN LIBROS%'";
    $deletedCxC = $db->exec($sqlCxC);
    echo "Ajustes de CxC Clientes (14M) eliminados: $deletedCxC\n";

    // 3. Caso específico Terrenos (Si aún quedaran duplicados por descripción ligeramente distinta)
    // Buscamos registros de Terrenos con montos idénticos en el mismo día
    $sqlTerr = "DELETE a1 FROM asientos a1
                JOIN puc_cuentas p1 ON a1.cuenta_id = p1.id
                INNER JOIN asientos a2 ON a1.fecha = a2.fecha AND a1.debito = a2.debito
                JOIN puc_cuentas p2 ON a2.cuenta_id = p2.id
                WHERE p1.codigo = '12010106' AND p2.codigo = '12010106'
                  AND a1.id > a2.id 
                  AND p1.empresa_id = 1";
    $deletedTerr = $db->exec($sqlTerr);
    echo "Duplicados residuales en Terrenos eliminados: $deletedTerr\n";

    $db->commit();
    echo "\nLIMPIEZA COMPLETADA EXITOSAMENTE.\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "ERROR DURANTE LA LIMPIEZA: " . $e->getMessage() . "\n";
}
?>
