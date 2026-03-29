<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requirePermiso('facturacion');

header('Content-Type: application/json');

$db  = Database::getInstance()->getPdo();
$eid = Auth::empresaId();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $stmt = $db->prepare("SELECT * FROM com_cai WHERE empresa_id = :eid ORDER BY fecha_limite DESC");
        $stmt->execute([':eid' => $eid]);
        echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } 
    elseif ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare(
            "INSERT INTO com_cai (empresa_id, punto_emision, establecimiento, cai, rango_desde, rango_hasta, consecutivo_actual, fecha_limite) 
             VALUES (:eid, :pe, :est, :cai, :rd, :rh, :ca, :fl)"
        );
        $stmt->execute([
            ':eid' => $eid,
            ':pe'  => trim($body['punto_emision']),
            ':est' => trim($body['establecimiento']),
            ':cai' => trim($body['cai']),
            ':rd'  => (int)$body['rango_desde'],
            ':rh'  => (int)$body['rango_hasta'],
            ':ca'  => (int)$body['consecutivo_actual'],
            ':fl'  => $body['fecha_limite']
        ]);
        echo json_encode(['success' => true]);
    }
    elseif ($method === 'PUT') {
        $body = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare(
            "UPDATE com_cai SET punto_emision = :pe, establecimiento = :est, cai = :cai, 
             rango_desde = :rd, rango_hasta = :rh, consecutivo_actual = :ca, fecha_limite = :fl 
             WHERE id = :id AND empresa_id = :eid"
        );
        $stmt->execute([
            ':pe'  => trim($body['punto_emision']),
            ':est' => trim($body['establecimiento']),
            ':cai' => trim($body['cai']),
            ':rd'  => (int)$body['rango_desde'],
            ':rh'  => (int)$body['rango_hasta'],
            ':ca'  => (int)$body['consecutivo_actual'],
            ':fl'  => $body['fecha_limite'],
            ':id'  => (int)$body['id'],
            ':eid' => $eid
        ]);
        echo json_encode(['success' => true]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
