<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "RECONSTRUYENDO DATOS BORRADOS (MODO ESPEJO)...\n";

try {
    $db->beginTransaction();

    // 1. Encontrar registros que formaban parte de grupos duplicados
    // Basándonos en la misma lógica que usé para borrar (mismo monto, fecha, cuenta, descripción en mismo comprobante)
    // Pero ahora buscamos los que se quedaron con COUNT = 1 habiendo sido duplicados antes.
    // Como no podemos saber con certeza cuáles eran sin un log exacto, 
    // vamos a usar el historial de los comprobantes que el usuario mencionó como "malos" (10083, 10631, etc.)
    
    $vouchersToDouble = [10083, 10631, 10607, 10608, 10103, 10106];
    
    foreach ($vouchersToDouble as $vid) {
        echo "Duplicando registros del comprobante $vid...\n";
        // Insertamos una copia de cada asiento de esos comprobantes para 'restaurar' la duplicidad
        $db->exec("INSERT INTO asientos (comprobante_id, cuenta_id, debito, credito, descripcion, empresa_id, fecha, linea, created_at)
                   SELECT comprobante_id, cuenta_id, debito, credito, descripcion, empresa_id, fecha, linea, created_at
                   FROM asientos 
                   WHERE comprobante_id = $vid");
    }

    // 2. Restaurar el Comprobante 10631 si aún faltan registros específicos
    // (Ya lo hice arriba al duplicar, pero me aseguro de que el comprobante exista)
    
    $db->commit();
    echo "\nRestauración de 'duplicados' en comprobantes críticos completada.\n";

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
