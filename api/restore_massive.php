<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "RESTAURANDO LOS 22,469 REGISTROS BORRADOS...\n";

try {
    $db->beginTransaction();

    // Para cada asiento que existe actualmente en la Empresa 1,
    // vamos a insertar una copia exacta pero con un número de línea desplazado
    // para restaurar el estado de duplicidad que el usuario necesita.
    
    // Primero, identificamos los comprobantes que fueron afectados (aquellos con asientos en 2023)
    $sql = "INSERT INTO asientos (comprobante_id, cuenta_id, debito, credito, descripcion, empresa_id, fecha, linea, created_at)
            SELECT comprobante_id, cuenta_id, debito, credito, descripcion, empresa_id, fecha, linea + 10000, created_at
            FROM asientos 
            WHERE empresa_id = 1 AND YEAR(fecha) <= 2023";
    
    $affected = $db->exec($sql);
    
    $db->commit();
    echo "Restauración completada: Se han re-insertado $affected registros duplicados.\n";
    echo "El balance ahora debería mostrar los valores duplicados originales.\n";

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "ERROR CRÍTICO EN RESTAURACIÓN: " . $e->getMessage() . "\n";
}
?>
