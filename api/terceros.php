<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

header('Content-Type: application/json; charset=utf-8');
Auth::requireAuth();

$db  = Database::getInstance()->getPdo();
$eid = Auth::empresaId();
$q   = trim($_GET['q'] ?? '');

try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM terceros WHERE id = :id AND empresa_id = :eid");
            $stmt->execute([':id' => $id, ':eid' => $eid]);
            $item = $stmt->fetch();
            if (!$item) throw new \RuntimeException('Tercero no encontrado.');
            echo json_encode(['data' => $item]);
        } else {
            $q    = trim($_GET['q'] ?? '');
            $tipo = trim($_GET['tipo'] ?? '');
            
            $sql = "SELECT id, codigo, nit_cc, razon_social, razon_social AS nombre, tipo_tercero 
                    FROM terceros 
                    WHERE empresa_id = :eid";
            $params = [':eid' => $eid];

            if ($tipo !== '') {
                $sql .= " AND (tipo_tercero LIKE :tipo OR tipo_tercero IS NULL OR tipo_tercero = '')";
                $params[':tipo'] = "%$tipo%";
            }

            if ($q !== '') {
                $sql .= " AND (LOWER(codigo) LIKE :q1 OR nit_cc LIKE :q2 OR LOWER(razon_social) LIKE :q3)";
                $params[':q1'] = "%" . strtolower($q) . "%";
                $params[':q2'] = "%" . strtolower($q) . "%";
                $params[':q3'] = "%" . strtolower($q) . "%";
            }

            $sql .= " ORDER BY razon_social ASC LIMIT 50";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['data' => $data]);
        }
    } 
    elseif ($method === 'POST' || $method === 'PUT') {
        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body) throw new \RuntimeException('Payload inválido.');

        $id      = $body['id'] ? (int)$body['id'] : null;
        $codigo  = trim($body['codigo']);
        $nombre  = trim($body['razon_social']);
        $nit     = trim($body['nit_cc']);
        $tipoP   = $body['tipo_persona'] ?: 'J';
        $tipoD   = $body['tipo_documento'] ?: 'RTN';
        $email   = $body['email'] ?: null;
        $tel     = $body['telefono'] ?: null;
        $dir     = $body['direccion'] ?: null;
        $ciu     = $body['ciudad'] ?: null;
        $tiposT  = is_array($body['tipo_tercero']) ? implode(',', $body['tipo_tercero']) : $body['tipo_tercero'];
        $activo  = (int)($body['activo'] ?? 1);

        if (!$codigo || !$nombre || !$nit) throw new \RuntimeException('Código, Nombre y RTN/DNI son obligatorios.');

        if ($id) {
            $stmt = $db->prepare(
                "UPDATE terceros SET 
                        codigo = :c, tipo_persona = :tp, tipo_documento = :td, nit_cc = :nit, 
                        razon_social = :rs, email = :e, telefono = :t, direccion = :d, ciudad = :ci,
                        tipo_tercero = :tt, activo = :a
                 WHERE id = :id AND empresa_id = :eid"
            );
            $stmt->execute([
                ':c'=>$codigo, ':tp'=>$tipoP, ':td'=>$tipoD, ':nit'=>$nit, ':rs'=>$nombre,
                ':e'=>$email, ':t'=>$tel, ':d'=>$dir, ':ci'=>$ciu, ':tt'=>$tiposT, ':a'=>$activo,
                ':id'=>$id, ':eid'=>$eid
            ]);
        } else {
            // Verificar duplicados
            $check = $db->prepare("SELECT id FROM terceros WHERE empresa_id = :eid AND (codigo = :c OR nit_cc = :nit)");
            $check->execute([':eid'=>$eid, ':c'=>$codigo, ':nit'=>$nit]);
            if ($check->fetch()) throw new \RuntimeException('El código o RTN ya está registrado.');

            $stmt = $db->prepare(
                "INSERT INTO terceros (empresa_id, codigo, tipo_persona, tipo_documento, nit_cc, razon_social, email, telefono, direccion, ciudad, tipo_tercero, activo)
                 VALUES (:eid, :c, :tp, :td, :nit, :rs, :e, :t, :d, :ci, :tt, :a)"
            );
            $stmt->execute([
                ':eid'=>$eid, ':c'=>$codigo, ':tp'=>$tipoP, ':td'=>$tipoD, ':nit'=>$nit, ':rs'=>$nombre,
                ':e'=>$email, ':t'=>$tel, ':d'=>$dir, ':ci'=>$ciu, ':tt'=>$tiposT, ':a'=>$activo
            ]);
        }
        echo json_encode(['success' => true]);
    }
    elseif ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) throw new \RuntimeException('ID inválido.');

        // Verificar si tiene movimientos
        $check = $db->prepare("SELECT 1 FROM comprobantes WHERE tercero_id = :id LIMIT 1");
        $check->execute([':id'=>$id]);
        if ($check->fetch()) throw new \RuntimeException('No se puede eliminar porque tiene comprobantes asociados.');

        $check2 = $db->prepare("SELECT 1 FROM asientos WHERE tercero_id = :id LIMIT 1");
        $check2->execute([':id'=>$id]);
        if ($check2->fetch()) throw new \RuntimeException('No se puede eliminar porque tiene asientos contables asociados.');

        $stmt = $db->prepare("DELETE FROM terceros WHERE id = :id AND empresa_id = :eid");
        $stmt->execute([':id' => $id, ':eid' => $eid]);
        echo json_encode(['success' => true]);
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

