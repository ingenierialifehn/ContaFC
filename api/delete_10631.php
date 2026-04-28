<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "ELIMINANDO COMPROBANTE DUPLICADO 10631...\n";

try {
    $db->beginTransaction();

    // 1. Eliminar asientos del comprobante 10631
    $stmt1 = $db->prepare("DELETE FROM asientos WHERE comprobante_id = 10631");
    $stmt1->execute();
    $asientosDeleted = $stmt1->rowCount();

    // 2. Eliminar el comprobante 10631
    $stmt2 = $db->prepare("DELETE FROM comprobantes WHERE id = 10631");
    $stmt2->execute();
    $compDeleted = $stmt2->rowCount();

    $db->commit();
    echo "Operación exitosa:\n";
    echo " - Asientos eliminados: $asientosDeleted\n";
    echo " - Comprobantes eliminados: $compDeleted\n";

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
