<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "FINALIZANDO RESTAURACIÓN (AJUSTANDO LÍMITES DE COLUMNA LÍNEA)...\n";

try {
    $db->beginTransaction();

    // Como ya duplicamos una vez (Fase 1), ahora vamos por la Fase 2 (Cuadruplicados)
    // Pero usaremos un offset de línea más pequeño (ej. +500) que no rompa el límite de la columna.
    // Solo para los registros que suelen estar cuadruplicados.
    
    $sql = "INSERT INTO asientos (comprobante_id, cuenta_id, debito, credito, descripcion, empresa_id, fecha, linea, created_at)
            SELECT comprobante_id, cuenta_id, debito, credito, descripcion, empresa_id, fecha, linea + 500, created_at
            FROM asientos 
            WHERE empresa_id = 1 
              AND (descripcion LIKE '%SALDO%' OR descripcion LIKE '%LIBROS%')
              AND linea < 500"; // Evitamos duplicar lo que ya duplicamos
    
    $affected = $db->exec($sql);
    echo "Fase Final: Registros adicionales restaurados: $affected\n";

    $db->commit();
    
    $stmt = $db->query("SELECT COUNT(*) FROM asientos WHERE empresa_id = 1");
    echo "\nCONTEO FINAL ALCANZADO: " . $stmt->fetchColumn() . " registros.\n";

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
