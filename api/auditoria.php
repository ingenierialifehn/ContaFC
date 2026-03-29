<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Services\AuditService;

Auth::requireAuth();

header('Content-Type: application/json');

$service = new AuditService();
$method  = $_SERVER['REQUEST_METHOD'];
$eid     = Auth::empresaId();

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'logs';
        if ($action === 'logs') {
            $filters = [
                'usuario_id' => $_GET['usuario_id'] ?? null,
                'tabla'      => $_GET['tabla']      ?? null,
                'accion'     => $_GET['accion']     ?? null,
                'desde'      => $_GET['desde']      ?? null,
                'hasta'      => $_GET['hasta']      ?? null,
            ];
            $data = $service->getLogs($eid, $filters);
            echo json_encode(['data' => $data]);
        }
        elseif ($action === 'consecutivos') {
            $tabla = $_GET['tabla'] ?? 'comprobantes';
            $tipoId = isset($_GET['tipo_id']) ? (int)$_GET['tipo_id'] : null;
            $res = $service->checkConsecutiveGaps($eid, $tabla, $tipoId);
            echo json_encode(['data' => $res]);
        }
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
