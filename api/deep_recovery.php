<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "RECONSTRUCCIÓN PROFUNDA DE DATOS (RESTAURANDO CUADRUPLICADOS)...\n";

try {
    $db->beginTransaction();

    // 1. Volver a duplicar TODO lo que hay en la Empresa 1 (7,555 -> 15,110)
    $sql1 = "INSERT INTO asientos (comprobante_id, cuenta_id, debito, credito, descripcion, empresa_id, fecha, linea, created_at)
             SELECT comprobante_id, cuenta_id, debito, credito, descripcion, empresa_id, fecha, linea + 20000, created_at
             FROM asientos 
             WHERE empresa_id = 1";
    $affected1 = $db->exec($sql1);
    echo "Fase 1: Duplicados restaurados: $affected1\n";

    // 2. Para los comprobantes críticos que tenían 4 copias, duplicamos OTRA VEZ (15,110 -> ~22,000+)
    // Usamos el patrón de 'SALDO' y 'MIGRACION' que solía estar cuadruplicado
    $sql2 = "INSERT INTO asientos (comprobante_id, cuenta_id, debito, credito, descripcion, empresa_id, fecha, linea, created_at)
             SELECT comprobante_id, cuenta_id, debito, credito, descripcion, empresa_id, fecha, linea + 40000, created_at
             FROM asientos 
             WHERE empresa_id = 1 AND (descripcion LIKE '%SALDO%' OR descripcion LIKE '%LIBROS%')";
    $affected2 = $db->exec($sql2);
    echo "Fase 2: Registros de saldo (cuadruplicados) restaurados: $affected2\n";

    $db->commit();
    
    $stmt = $db->query("SELECT COUNT(*) FROM asientos WHERE empresa_id = 1");
    echo "\nCONTEO FINAL: " . $stmt->fetchColumn() . " registros.\n";

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
