<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

header('Content-Type: application/json; charset=utf-8');
Auth::requireAuth();
Auth::requireRol('admin');

$db     = Database::getInstance()->getPdo();
$method = $_SERVER['REQUEST_METHOD'];

try {
    // ─── GET: listar usuarios ─────────────────────────────────────────────
    if ($method === 'GET') {
        $stmt = $db->query(
            "SELECT id, username, nombre, email, rol, activo, permisos FROM usuarios ORDER BY nombre"
        );
        $usuarios = $stmt->fetchAll();

        foreach ($usuarios as &$u) {
            $u['permisos'] = json_decode($u['permisos'] ?: '{}', true) ?? [];

            $stmtEmp = $db->prepare(
                "SELECT e.id, e.nombre FROM empresas e
                 INNER JOIN usuarios_empresas ue ON ue.empresa_id = e.id
                 WHERE ue.usuario_id = :uid"
            );
            $stmtEmp->execute([':uid' => $u['id']]);
            $u['empresas'] = $stmtEmp->fetchAll();
        }
        unset($u);

        echo json_encode(['data' => $usuarios], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Leer body para POST / PUT / PATCH / DELETE
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    // ─── POST: crear usuario ──────────────────────────────────────────────
    if ($method === 'POST') {
        $username    = trim($body['username'] ?? '');
        $nombre      = trim($body['nombre'] ?? '');
        $email       = trim($body['email'] ?? '');
        $rol         = $body['rol'] ?? 'consulta';
        $password    = $body['password'] ?? '';
        $permisos    = json_encode($body['permisos'] ?? [], JSON_UNESCAPED_UNICODE);
        $empresa_ids = $body['empresa_ids'] ?? [];

        if (!$username || !$nombre) {
            throw new \RuntimeException('Usuario y Nombre son obligatorios.');
        }
        if (!$password || strlen($password) < 6) {
            throw new \RuntimeException('Contraseña requerida (mínimo 6 caracteres).');
        }

        // Verificar username único
        $check = $db->prepare("SELECT id FROM usuarios WHERE username = :u");
        $check->execute([':u' => $username]);
        if ($check->fetch()) {
            throw new \RuntimeException("El username '$username' ya existe en el sistema.");
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $db->beginTransaction();
        $stmt = $db->prepare(
            "INSERT INTO usuarios (username, nombre, email, rol, permisos, password_hash, activo, created_at)
             VALUES (:u, :n, :e, :r, :p, :h, 1, NOW())"
        );
        $stmt->execute([
            ':u' => $username, ':n' => $nombre, ':e' => $email,
            ':r' => $rol, ':p' => $permisos, ':h' => $hash,
        ]);
        $newId = (int)$db->lastInsertId();

        // Sincronizar empresas
        $stmtIns = $db->prepare("INSERT INTO usuarios_empresas (usuario_id, empresa_id) VALUES (?, ?)");
        foreach ($empresa_ids as $eid) {
            $stmtIns->execute([$newId, (int)$eid]);
        }

        $db->commit();
        echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Usuario creado correctamente.']);
        exit;
    }

    // ─── PUT: actualizar usuario ──────────────────────────────────────────
    if ($method === 'PUT') {
        $id          = (int)($body['id'] ?? 0);
        $username    = trim($body['username'] ?? '');
        $nombre      = trim($body['nombre'] ?? '');
        $email       = trim($body['email'] ?? '');
        $rol         = $body['rol'] ?? 'consulta';
        $permisos    = json_encode($body['permisos'] ?? [], JSON_UNESCAPED_UNICODE);
        $empresa_ids = $body['empresa_ids'] ?? [];
        $newPass     = $body['password'] ?? '';

        if (!$id) throw new \RuntimeException('ID requerido para actualizar.');

        $db->beginTransaction();

        if ($newPass) {
            $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->prepare(
                "UPDATE usuarios SET username=:u, nombre=:n, email=:e, rol=:r, permisos=:p, password_hash=:h WHERE id=:id"
            )->execute([':u'=>$username,':n'=>$nombre,':e'=>$email,':r'=>$rol,':p'=>$permisos,':h'=>$hash,':id'=>$id]);
        } else {
            $db->prepare(
                "UPDATE usuarios SET username=:u, nombre=:n, email=:e, rol=:r, permisos=:p WHERE id=:id"
            )->execute([':u'=>$username,':n'=>$nombre,':e'=>$email,':r'=>$rol,':p'=>$permisos,':id'=>$id]);
        }

        // Sincronizar empresas
        $db->prepare("DELETE FROM usuarios_empresas WHERE usuario_id = ?")->execute([$id]);
        $stmtIns = $db->prepare("INSERT INTO usuarios_empresas (usuario_id, empresa_id) VALUES (?, ?)");
        foreach ($empresa_ids as $eid) {
            $stmtIns->execute([$id, (int)$eid]);
        }

        $db->commit();

        // Refrescar caché de empresas en sesión si es el usuario activo
        if (Auth::userId() === $id) {
            unset($_SESSION['user']['empresas']);
        }

        echo json_encode(['success' => true, 'message' => 'Usuario actualizado.']);
        exit;
    }

    // ─── PATCH: toggle activo ─────────────────────────────────────────────
    if ($method === 'PATCH') {
        $id     = (int)($body['id'] ?? 0);
        $activo = (int)($body['activo'] ?? 0);
        if (!$id) throw new \RuntimeException('ID requerido.');
        $db->prepare("UPDATE usuarios SET activo = :a WHERE id = :id")
           ->execute([':a' => $activo, ':id' => $id]);
        echo json_encode(['success' => true]);
        exit;
    }

    // ─── DELETE: eliminar usuario ─────────────────────────────────────────
    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? $body['id'] ?? 0);
        if (!$id) throw new \RuntimeException('ID requerido.');

        // Proteger admin principal
        $chk = $db->prepare("SELECT rol FROM usuarios WHERE id = ?");
        $chk->execute([$id]);
        $target = $chk->fetch();
        if ($target && $target['rol'] === 'admin' && Auth::userId() === $id) {
            throw new \RuntimeException('No puedes eliminar tu propio usuario administrador.');
        }

        $db->beginTransaction();
        $db->prepare("DELETE FROM usuarios_empresas WHERE usuario_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM usuarios WHERE id = ? AND rol != 'admin'")->execute([$id]);
        $db->commit();
        echo json_encode(['success' => true]);
        exit;
    }

    throw new \RuntimeException('Método HTTP no soportado.');

} catch (\Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
