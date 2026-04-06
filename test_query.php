<?php
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Database;

try {
    $db = Database::getInstance()->getPdo();
    $eid = 1; // Suponiendo empresa 1
    $desde = '2023-01-01';
    $hasta = '2023-12-31';
    $estado = 'registrado';
    
    $stmt = $db->prepare(
        "SELECT c.*, tc.codigo as tipo_comp, tc.nombre as tipo_nombre, t.nombre as tercero,
                (SELECT SUM(debito) FROM asientos WHERE comprobante_id = c.id) as total_debitos,
                (SELECT SUM(credito) FROM asientos WHERE comprobante_id = c.id) as total_creditos
         FROM comprobantes c
         JOIN tipos_comprobante tc ON c.tipo_comp_id = tc.id
         LEFT JOIN terceros t ON c.tercero_id = t.id
         WHERE c.empresa_id = :eid AND c.fecha BETWEEN :d AND :h AND c.estado = :e
         ORDER BY c.fecha DESC, c.numero DESC"
    );
    $stmt->execute([':eid' => $eid, ':d' => $desde, ':h' => $hasta, ':e' => $estado]);
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "🔍 Resultados para Empresa 1 en 2023:\n";
    if (!$res) echo "❌ NADA ENCONTRADO EN EL QUERY.\n";
    foreach ($res as $row) {
        echo "ID: {$row['id']} - Tipo: {$row['tipo_comp']} - Numero: {$row['numero']} - Fecha: {$row['fecha']} - Deb: {$row['total_debitos']} - Cre: {$row['total_creditos']}\n";
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
