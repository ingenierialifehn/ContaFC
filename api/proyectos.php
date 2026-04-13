<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requirePermiso('puc'); // Mismo permiso que Centros de Costo

header('Content-Type: application/json');

$db  = Database::getInstance()->getPdo();
$db->exec("ALTER TABLE proyectos ADD COLUMN IF NOT EXISTS logo_path VARCHAR(255) DEFAULT NULL");
$eid = Auth::empresaId();
$method = $_SERVER['REQUEST_METHOD'];
$isMultipart = str_contains(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'multipart/form-data');

function getProyectosRequestData(): array {
    if (!empty($_POST)) return $_POST;
    $body = json_decode(file_get_contents('php://input'), true);
    return is_array($body) ? $body : [];
}

try {
    if ($method === 'GET') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM proyectos WHERE id = :id AND empresa_id = :eid");
            $stmt->execute([':id' => $id, ':eid' => $eid]);
            echo json_encode(['data' => $stmt->fetch()]);
        } else {
            if (Auth::user()['rol'] === 'admin') {
                $stmt = $db->prepare("SELECT * FROM proyectos WHERE empresa_id = :eid ORDER BY codigo ASC");
                $stmt->execute([':eid' => $eid]);
            } else {
                $stmt = $db->prepare("
                    SELECT p.* 
                    FROM proyectos p
                    INNER JOIN usuarios_proyectos up ON p.id = up.proyecto_id
                    WHERE p.empresa_id = :eid AND up.usuario_id = :uid
                    ORDER BY p.codigo ASC
                ");
                $stmt->execute([':eid' => $eid, ':uid' => Auth::userId()]);
            }
            echo json_encode(['data' => $stmt->fetchAll()]);
        }
    } elseif ($method === 'POST') {
        $requestedMethod = strtoupper((string)($_POST['_method'] ?? 'POST'));
        $data = getProyectosRequestData();
        
        $id = !empty($data['id']) ? (int)$data['id'] : null;

        if ($requestedMethod === 'PUT' || $id) {
            if (!$id) throw new Exception('ID faltante.');
            $stmtCurrent = $db->prepare("SELECT logo_path FROM proyectos WHERE id = :id AND empresa_id = :eid");
            $stmtCurrent->execute([':id' => $id, ':eid' => $eid]);
            $current = $stmtCurrent->fetch();
            if (!$current) throw new Exception("Proyecto no encontrado.");
            
            $logoPath = $current['logo_path'] ?? null;
            $removeLogo = (int)($data['remove_logo'] ?? 0) === 1;

            if ($removeLogo && $logoPath) {
                if (file_exists(__DIR__ . '/../' . $logoPath)) unlink(__DIR__ . '/../' . $logoPath);
                $logoPath = null;
            }

            if ($isMultipart && isset($_FILES['logo']) && (int)($_FILES['logo']['error']) !== UPLOAD_ERR_NO_FILE) {
                if ($logoPath && file_exists(__DIR__ . '/../' . $logoPath)) unlink(__DIR__ . '/../' . $logoPath);
                $filename = 'proyecto_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.jpg';
                $target = __DIR__ . '/../uploads/proyectos';
                if (!is_dir($target)) mkdir($target, 0775, true);
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $target . '/' . $filename)) {
                    $logoPath = 'uploads/proyectos/' . $filename;
                }
            }

            $stmt = $db->prepare(
                "UPDATE proyectos SET codigo = :cod, nombre = :nom, activo = :act, logo_path = :logo 
                 WHERE id = :id AND empresa_id = :eid"
            );
            $stmt->execute([
                ':cod' => trim($data['codigo']),
                ':nom' => trim($data['nombre']),
                ':act' => (int)($data['activo'] ?? 1),
                ':logo' => $logoPath,
                ':id'  => $id,
                ':eid' => $eid
            ]);
            echo json_encode(['success' => true]);
        } else {
            // Validar si el código ya existe para esta empresa
            $check = $db->prepare("SELECT id FROM proyectos WHERE empresa_id = :eid AND codigo = :cod");
            $check->execute([':eid' => $eid, ':cod' => trim($data['codigo'])]);
            if ($check->fetch()) {
                throw new Exception("El código de proyecto ya está en uso para esta empresa.");
            }

            $logoPath = null;
            if ($isMultipart && isset($_FILES['logo']) && (int)($_FILES['logo']['error']) !== UPLOAD_ERR_NO_FILE) {
                $filename = 'proyecto_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.jpg';
                $target = __DIR__ . '/../uploads/proyectos';
                if (!is_dir($target)) mkdir($target, 0775, true);
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $target . '/' . $filename)) {
                    $logoPath = 'uploads/proyectos/' . $filename;
                }
            }

            $stmt = $db->prepare(
                "INSERT INTO proyectos (empresa_id, codigo, nombre, activo, logo_path) 
                 VALUES (:eid, :cod, :nom, :act, :logo)"
            );
            $stmt->execute([
                ':eid' => $eid,
                ':cod' => trim($data['codigo']),
                ':nom' => trim($data['nombre']),
                ':act' => (int)($data['activo'] ?? 1),
                ':logo' => $logoPath
            ]);
            echo json_encode(['success' => true]);
        }
    }
    elseif ($method === 'PUT') {
        // Fallback para method PUT directo
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

        $stmtCurrent = $db->prepare("SELECT logo_path FROM proyectos WHERE id = :id AND empresa_id = :eid");
        $stmtCurrent->execute([':id' => $id, ':eid' => $eid]);
        $logoPath = $stmtCurrent->fetchColumn();
        if ($logoPath && file_exists(__DIR__ . '/../' . $logoPath)) unlink(__DIR__ . '/../' . $logoPath);

        $stmt = $db->prepare("DELETE FROM proyectos WHERE id = :id AND empresa_id = :eid");
        $stmt->execute([':id' => $id, ':eid' => $eid]);
        echo json_encode(['success' => true]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
