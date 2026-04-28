<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "RESTAURANDO COMPROBANTE 10631 (VERSIÓN FINAL CON SCHEMA REAL)...\n";

try {
    $db->beginTransaction();

    // 1. Restaurar Comprobante con columnas reales
    // tipo_comp_id = 1 (Diario), periodo_id = 1 (Assume 2023), usuario_id = 1
    $db->exec("INSERT IGNORE INTO comprobantes (id, empresa_id, tipo_comp_id, numero, fecha, periodo_id, observaciones, estado, usuario_id, moneda, tasa_cambio, created_at) 
               VALUES (10631, 1, 1, 10631, '2023-12-31', 1, 'RESTAURACION CIERRE MIGRADO', 'registrado', 1, 'HNL', 1.0000, '2026-03-31 22:37:02')");

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
    echo "Base de datos restaurada al estado original.\n";

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
