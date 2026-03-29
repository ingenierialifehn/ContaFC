<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
header('Content-Type: application/json');

$db  = Database::getInstance()->getPdo();
$eid = Auth::empresaId();
$pid = isset($_GET['producto_id']) ? (int)$_GET['producto_id'] : null;

if (!$pid) {
    http_response_code(400); echo json_encode(['error' => 'Producto no especificado']); exit;
}

try {
    // Listar lotes/seriales con stock activo para el producto
    $stmt = $db->prepare(
        "SELECT * FROM com_trazabilidad 
         WHERE empresa_id = :eid AND producto_id = :pid AND stock_actual > 0
         ORDER BY (fecha_vence IS NULL), fecha_vence ASC" // FIFO por vencimiento
    );
    $stmt->execute([':eid' => $eid, ':pid' => $pid]);
    echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
}
