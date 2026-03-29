<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requirePermiso('puc');

header('Content-Type: application/json');

$db  = Database::getInstance()->getPdo();
$eid = Auth::empresaId();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM tipos_comprobante WHERE id = :id AND empresa_id = :eid");
            $stmt->execute([':id' => $id, ':eid' => $eid]);
            echo json_encode(['data' => $stmt->fetch()]);
        } else {
            $stmt = $db->prepare("SELECT * FROM tipos_comprobante WHERE empresa_id = :eid ORDER BY codigo ASC");
            $stmt->execute([':eid' => $eid]);
            echo json_encode(['data' => $stmt->fetchAll()]);
        }
    } 
    elseif ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare(
            "INSERT INTO tipos_comprobante (empresa_id, codigo, nombre, activo) 
             VALUES (:eid, :cod, :nom, :act)"
        );
        $stmt->execute([
            ':eid' => $eid,
            ':cod' => trim($body['codigo']),
            ':nom' => trim($body['nombre']),
            ':act' => (int)($body['activo'] ?? 1)
        ]);
        echo json_encode(['success' => true]);
    }
    elseif ($method === 'PUT') {
        $body = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare(
            "UPDATE tipos_comprobante SET codigo = :cod, nombre = :nom, activo = :act 
             WHERE id = :id AND empresa_id = :eid"
        );
        $stmt->execute([
            ':cod' => trim($body['codigo']),
            ':nom' => trim($body['nombre']),
            ':act' => (int)($body['activo'] ?? 1),
            ':id'  => (int)$body['id'],
            ':eid' => $eid
        ]);
        echo json_encode(['success' => true]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
