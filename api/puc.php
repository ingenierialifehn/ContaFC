<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

header('Content-Type: application/json; charset=utf-8');
Auth::requireAuth();
Auth::requirePermiso('puc');

$db  = Database::getInstance()->getPdo();
$eid = Auth::empresaId();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM puc_cuentas WHERE id = :id AND empresa_id = :eid");
            $stmt->execute([':id' => $id, ':eid' => $eid]);
            $item = $stmt->fetch();
            if (!$item) throw new \RuntimeException('Cuenta no encontrada.');
            echo json_encode(['data' => $item]);
        } else {
            $stmt = $db->prepare("SELECT * FROM puc_cuentas WHERE empresa_id = :eid ORDER BY codigo ASC");
            $stmt->execute([':eid' => $eid]);
            echo json_encode(['data' => $stmt->fetchAll()]);
        }
    } 
    elseif ($method === 'POST' || $method === 'PUT') {
        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body) throw new \RuntimeException('Payload inválido.');

        $id = $body['id'] ? (int)$body['id'] : null;
        $codigo = trim($body['codigo']);
        $nombre = trim($body['nombre']);
        $padre  = $body['codigo_padre'] ?: null;
        $nivel  = (int)$body['nivel'];
        $nature = $body['naturaleza'];
        $tipo   = $body['tipo_cuenta'];
        $mov    = (int)$body['acepta_movimiento'];
        $activa = (int)($body['activa'] ?? 1);

        if ($id) {
            $stmt = $db->prepare(
                "UPDATE puc_cuentas SET codigo = :c, nombre = :n, nivel = :nv, codigo_padre = :cp, 
                        naturaleza = :nat, tipo_cuenta = :t, acepta_movimiento = :m, activa = :a
                 WHERE id = :id AND empresa_id = :eid"
            );
            $stmt->execute([':c'=>$codigo, ':n'=>$nombre, ':nv'=>$nivel, ':cp'=>$padre, ':nat'=>$nature, ':t'=>$tipo, ':m'=>$mov, ':a'=>$activa, ':id'=>$id, ':eid'=>$eid]);
        } else {
            // Verificar si el código ya existe
            $check = $db->prepare("SELECT 1 FROM puc_cuentas WHERE empresa_id = :eid AND codigo = :c");
            $check->execute([':eid'=>$eid, ':c'=>$codigo]);
            if ($check->fetch()) throw new \RuntimeException("El código {$codigo} ya está registrado.");

            $stmt = $db->prepare(
                "INSERT INTO puc_cuentas (empresa_id, codigo, nombre, nivel, codigo_padre, naturaleza, tipo_cuenta, acepta_movimiento, activa)
                 VALUES (:eid, :c, :n, :nv, :cp, :nat, :t, :m, :a)"
            );
            $stmt->execute([':eid'=>$eid, ':c'=>$codigo, ':n'=>$nombre, ':nv'=>$nivel, ':cp'=>$padre, ':nat'=>$nature, ':t'=>$tipo, ':m'=>$mov, ':a'=>$activa]);
        }
        echo json_encode(['success' => true]);
    }
    elseif ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) throw new \RuntimeException('ID inválido.');

        // Verificar si tiene movimientos en asientos
        $check = $db->prepare("SELECT 1 FROM asientos WHERE cuenta_id = :id LIMIT 1");
        $check->execute([':id' => $id]);
        if ($check->fetch()) throw new \RuntimeException('No se puede eliminar la cuenta porque ya tiene movimientos contables.');

        // Verificar si tiene subcuentas
        $stmtC = $db->prepare("SELECT codigo FROM puc_cuentas WHERE id = :id LIMIT 1");
        $stmtC->execute([':id' => $id]);
        $codigo = $stmtC->fetchColumn();
        
        $checkSub = $db->prepare("SELECT 1 FROM puc_cuentas WHERE codigo_padre = :c AND empresa_id = :eid LIMIT 1");
        $checkSub->execute([':c' => $codigo, ':eid' => $eid]);
        if ($checkSub->fetch()) throw new \RuntimeException('No se puede eliminar porque tiene subcuentas vinculadas.');

        $stmt = $db->prepare("DELETE FROM puc_cuentas WHERE id = :id AND empresa_id = :eid");
        $stmt->execute([':id' => $id, ':eid' => $eid]);
        echo json_encode(['success' => true]);
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
