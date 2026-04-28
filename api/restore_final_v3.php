<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "RESTAURANDO COMPROBANTE 10631 (VERSIÓN CORREGIDA 3)...\n";

try {
    $db->beginTransaction();

    // 1. Restaurar Comprobante (Columna correcta es 'tipo', no 'tipo_id')
    $db->exec("INSERT IGNORE INTO comprobantes (id, tipo, numero, fecha, descripcion, estado, empresa_id, created_at) 
               VALUES (10631, 'DIARIO', 10631, '2023-12-31', 'CIERRE DE EJERCICIO MIGRADO', 'registrado', 1, '2026-03-31 22:37:02')");

    // 2. Restaurar Asientos
    $seats = [
        ['cuenta_id' => 85, 'debito' => 17830.00, 'credito' => 0, 'desc' => 'SALDO SEGÚN LIBROS'],
        ['cuenta_id' => 3, 'debito' => 34204333.00, 'credito' => 0, 'desc' => 'SALDO SEGÚN LIBROS'],
        ['cuenta_id' => 110, 'debito' => 257445.00, 'credito' => 0, 'desc' => 'PÉRDIDA DEL PERIODO']
    ];

    foreach ($seats as $s) {
        $stmt = $db->prepare("INSERT INTO asientos (comprobante_id, cuenta_id, debito, credito, descripcion, empresa_id, fecha, created_at) 
                              VALUES (10631, :acc_id, :deb, :cre, :desc, 1, '2023-12-31', '2026-03-31 22:37:02')");
        $stmt->execute([
            ':acc_id' => $s['cuenta_id'],
            ':deb' => $s['debito'],
            ':cre' => $s['credito'],
            ':desc' => $s['desc']
        ]);
    }

    $db->commit();
    echo "Restauración de base de datos completada.\n";

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
