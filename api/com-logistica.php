<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
header('Content-Type: application/json');

$db  = Database::getInstance()->getPdo();
$eid = Auth::empresaId();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $estado = $_GET['estado'] ?? 'pendiente';
        
        $stmt = $db->prepare(
            "SELECT f.id, f.numero_factura, f.fecha, t.razon_social, f.estado_logistico, t.direccion,
                    (SELECT SUM(cantidad) FROM com_facturas_detalle WHERE factura_id = f.id) as total_items
             FROM com_facturas f
             JOIN terceros t ON f.tercero_id = t.id
             WHERE f.empresa_id = :eid AND f.estado_logistico = :est 
             ORDER BY f.fecha ASC"
        );
        $stmt->execute([':eid' => $eid, ':est' => $estado]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Conteos rápidos
        $counts = [
            'pendiente' => $db->query("SELECT COUNT(*) FROM com_facturas WHERE empresa_id = $eid AND estado_logistico = 'pendiente'")->fetchColumn(),
            'despachado' => $db->query("SELECT COUNT(*) FROM com_facturas WHERE empresa_id = $eid AND estado_logistico = 'despachado'")->fetchColumn()
        ];

        echo json_encode(['data' => $data, 'counts' => $counts]);
    } 
    elseif ($method === 'PUT') {
        $body = json_decode(file_get_contents('php://input'), true);
        if (empty($body['id'])) throw new Exception("ID de factura requerido");

        $stmt = $db->prepare(
            "UPDATE com_facturas SET estado_logistico = :est, transportista = :trans, tracking_num = :track 
             WHERE id = :id AND empresa_id = :eid"
        );
        $stmt->execute([
            ':est'   => $body['nuevo_estado'],
            ':trans' => $body['transportista'] ?? null,
            ':track' => $body['tracking'] ?? null,
            ':id'    => (int)$body['id'],
            ':eid'   => $eid
        ]);
        echo json_encode(['success' => true]);
    }
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
}
