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
        $stmt = $db->prepare(
            "SELECT c.*, t.razon_social, p.nombre as nombre_prod 
             FROM com_contratos c
             JOIN terceros t ON c.cliente_id = t.id
             JOIN com_productos p ON c.producto_id = p.id
             WHERE c.empresa_id = :eid AND c.activa = 1
             ORDER BY c.id DESC"
        );
        $stmt->execute([':eid' => $eid]);
        echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } 
    elseif ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body['cliente_id'] || !$body['producto_id']) throw new Exception("Faltan datos obligatorios.");

        $stmt = $db->prepare(
            "INSERT INTO com_contratos (empresa_id, cliente_id, producto_id, monto, dia_facturacion, fecha_inicio, activa) 
             VALUES (:eid, :cid, :pid, :mon, :dia, :fec, 1)"
        );
        $stmt->execute([
            ':eid' => $eid,
            ':cid' => (int)$body['cliente_id'],
            ':pid' => (int)$body['producto_id'],
            ':mon' => (float)$body['monto'],
            ':dia' => (int)$body['dia'],
            ':fec' => $body['fecha_inicio']
        ]);
        echo json_encode(['success' => true]);
    }
    elseif ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) throw new Exception("ID inválido");
        $stmt = $db->prepare("DELETE FROM com_contratos WHERE id = :id AND empresa_id = :eid");
        $stmt->execute([':id' => $id, ':eid' => $eid]);
        echo json_encode(['success' => true]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
