<?php
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Auth;
use ContaFC\Core\Database;

// Simular login de usuario 1 empresa 1
$_SESSION['user_id'] = 1;
$_SESSION['empresa_id'] = 1;

$_GET['desde'] = '2023-01-01';
$_GET['hasta'] = '2026-12-31';
$_GET['estado'] = 'registrado';

try {
    $db = Database::getInstance()->getPdo();
    $eid = 1; // Auth::empresaId();
    $desde = '2023-01-01';
    $hasta = '2026-12-31';
    $estado = 'registrado';

    $stmt = $db->prepare(
        "SELECT c.*, tc.codigo as tipo_comp, tc.nombre as tipo_nombre, t.razon_social as tercero,
                (SELECT SUM(debito) FROM asientos WHERE comprobante_id = c.id) as total_debitos,
                (SELECT SUM(credito) FROM asientos WHERE comprobante_id = c.id) as total_creditos
         FROM comprobantes c
         JOIN tipos_comprobante tc ON c.tipo_comp_id = tc.id
         LEFT JOIN terceros t ON c.tercero_id = t.id
         WHERE c.empresa_id = :eid AND c.fecha BETWEEN :d AND :h AND c.estado = :e
         ORDER BY c.fecha DESC, c.numero DESC"
    );
    $stmt->execute([':eid' => $eid, ':d' => $desde, ':h' => $hasta, ':e' => $estado]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "COUNT: " . count($data) . "\n";
    if (count($data) > 0) {
        print_r($data[0]);
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
