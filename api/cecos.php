<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requirePermiso('puc'); // Usamos mismo permiso que PUC o uno de configuración

header('Content-Type: application/json');

$db  = Database::getInstance()->getPdo();
$eid = Auth::empresaId();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM centros_costo WHERE id = :id AND empresa_id = :eid");
            $stmt->execute([':id' => $id, ':eid' => $eid]);
            echo json_encode(['data' => $stmt->fetch()]);
        } else {
            $stmt = $db->prepare("SELECT * FROM centros_costo WHERE empresa_id = :eid ORDER BY codigo ASC");
            $stmt->execute([':eid' => $eid]);
            echo json_encode(['data' => $stmt->fetchAll()]);
        }
    } 
    elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare(
            "INSERT INTO centros_costo (empresa_id, codigo, nombre, activa) 
             VALUES (:eid, :cod, :nom, :act)"
        );
        $stmt->execute([
            ':eid' => $eid,
            ':cod' => trim($data['codigo']),
            ':nom' => trim($data['nombre']),
            ':act' => (int)($data['activa'] ?? 1)
        ]);
        echo json_encode(['success' => true]);
    }
    elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare(
            "UPDATE centros_costo SET codigo = :cod, nombre = :nom, activa = :act 
             WHERE id = :id AND empresa_id = :eid"
        );
        $stmt->execute([
            ':cod' => trim($data['codigo']),
            ':nom' => trim($data['nombre']),
            ':act' => (int)($data['activa'] ?? 1),
            ':id'  => (int)$data['id'],
            ':eid' => $eid
        ]);
        echo json_encode(['success' => true]);
    }
    elseif ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM centros_costo WHERE id = :id AND empresa_id = :eid");
        $stmt->execute([':id' => $id, ':eid' => $eid]);
        echo json_encode(['success' => true]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
