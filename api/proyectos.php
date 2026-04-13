<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requirePermiso('puc'); // Mismo permiso que Centros de Costo

header('Content-Type: application/json');

$db  = Database::getInstance()->getPdo();
$eid = Auth::empresaId();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM proyectos WHERE id = :id AND empresa_id = :eid");
            $stmt->execute([':id' => $id, ':eid' => $eid]);
            echo json_encode(['data' => $stmt->fetch()]);
        } else {
            $stmt = $db->prepare("SELECT * FROM proyectos WHERE empresa_id = :eid ORDER BY codigo ASC");
            $stmt->execute([':eid' => $eid]);
            echo json_encode(['data' => $stmt->fetchAll()]);
        }
    } 
    elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar si el código ya existe para esta empresa
        $check = $db->prepare("SELECT id FROM proyectos WHERE empresa_id = :eid AND codigo = :cod");
        $check->execute([':eid' => $eid, ':cod' => trim($data['codigo'])]);
        if ($check->fetch()) {
            throw new Exception("El código de proyecto ya está en uso para esta empresa.");
        }

        $stmt = $db->prepare(
            "INSERT INTO proyectos (empresa_id, codigo, nombre, activo) 
             VALUES (:eid, :cod, :nom, :act)"
        );
        $stmt->execute([
            ':eid' => $eid,
            ':cod' => trim($data['codigo']),
            ':nom' => trim($data['nombre']),
            ':act' => (int)($data['activo'] ?? 1)
        ]);
        echo json_encode(['success' => true]);
    }
    elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare(
            "UPDATE proyectos SET codigo = :cod, nombre = :nom, activo = :act 
             WHERE id = :id AND empresa_id = :eid"
        );
        $stmt->execute([
            ':cod' => trim($data['codigo']),
            ':nom' => trim($data['nombre']),
            ':act' => (int)($data['activo'] ?? 1),
            ':id'  => (int)$data['id'],
            ':eid' => $eid
        ]);
        echo json_encode(['success' => true]);
    }
    elseif ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        
        // Verificar si tiene movimientos antes de borrar
        $movements = $db->prepare("SELECT id FROM asientos WHERE proyecto_id = :id AND empresa_id = :eid LIMIT 1");
        $movements->execute([':id' => $id, ':eid' => $eid]);
        if ($movements->fetch()) {
            throw new Exception("No se puede eliminar el proyecto porque tiene movimientos contables asociados.");
        }

        $stmt = $db->prepare("DELETE FROM proyectos WHERE id = :id AND empresa_id = :eid");
        $stmt->execute([':id' => $id, ':eid' => $eid]);
        echo json_encode(['success' => true]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
