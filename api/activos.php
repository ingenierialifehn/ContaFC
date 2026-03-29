<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Services\FixedAssetService;

Auth::requireAuth();
// Auth::requirePermiso('activos'); // Podríamos agregar este permiso luego

header('Content-Type: application/json');

$service = new FixedAssetService();
$method  = $_SERVER['REQUEST_METHOD'];
$eid     = Auth::empresaId();

try {
    if ($method === 'GET') {
        $data = $service->getAll($eid);
        echo json_encode(['data' => $data]);
    } 
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Si es una acción especial (Procesar Depreciación)
        if (isset($input['action']) && $input['action'] === 'depreciate') {
            $periodoId = (int)$input['periodo_id'];
            $res = $service->processMonthlyDepreciation($eid, $periodoId, Auth::userId());
            echo json_encode($res);
            exit;
        }

        // Crear nuevo activo
        $input['empresa_id'] = $eid;
        $id = $service->create($input);
        echo json_encode(['success' => true, 'id' => $id]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
