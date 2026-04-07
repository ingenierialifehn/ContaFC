<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Services\CarteraService;

Auth::requireAuth();
header('Content-Type: application/json; charset=utf-8');

$svc      = new CarteraService();
$empresaId = Auth::empresaId();
$method    = $_SERVER['REQUEST_METHOD'];
$action    = $_GET['action'] ?? '';

try {
    // — GET: listados ————————————————————————————————————————————————
    if ($method === 'GET') {
        switch ($action) {
            case 'creditos':
                $estado = $_GET['estado'] ?? 'activo';
                echo json_encode(['success' => true, 'data' => $svc->getCreditos($empresaId, $estado)]);
                break;

            case 'cuotas':
                $cid = (int)($_GET['credito_id'] ?? 0);
                if (!$cid) throw new RuntimeException('credito_id requerido');
                echo json_encode(['success' => true, 'data' => $svc->getCuotas($cid)]);
                break;

            case 'estado_cuenta':
                $cid = (int)($_GET['credito_id'] ?? 0);
                if (!$cid) throw new RuntimeException('credito_id requerido');
                echo json_encode(['success' => true, 'data' => $svc->getEstadoCuenta($cid, $empresaId)]);
                break;

            case 'recaudos':
                echo json_encode(['success' => true, 'data' => $svc->getRecaudos($empresaId)]);
                break;

            default:
                throw new RuntimeException('Acción GET no válida');
        }
        exit;
    }

    // — POST: creación ———————————————————————————————————————————————
    if ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        switch ($action) {
            case 'credito':
                $id = $svc->createCredito($body, $empresaId);
                echo json_encode(['success' => true, 'id' => $id, 'message' => 'Crédito creado con tabla de amortización.']);
                break;

            case 'recaudo':
                $id = $svc->createRecaudo($body, $empresaId);
                echo json_encode(['success' => true, 'id' => $id, 'message' => 'Recaudo aplicado correctamente.']);
                break;

            default:
                throw new RuntimeException('Acción POST no válida');
        }
        exit;
    }

    // — DELETE ————————————————————————————————————————————————————————
    if ($method === 'DELETE') {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = (int)($body['id'] ?? 0);
        if (!$id) throw new RuntimeException('id requerido');
        $svc->deleteCredito($id, $empresaId);
        echo json_encode(['success' => true, 'message' => 'Crédito anulado.']);
        exit;
    }

    throw new RuntimeException('Método HTTP no soportado');

} catch (\Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
