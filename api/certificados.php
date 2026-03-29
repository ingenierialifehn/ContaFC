<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Services\TaxService;

Auth::requireAuth();

header('Content-Type: application/json');

$service = new TaxService();
$method  = $_SERVER['REQUEST_METHOD'];
$eid     = Auth::empresaId();

try {
    if ($method === 'GET') {
        $data = $service->getCertificates($eid);
        echo json_encode(['data' => $data]);
    } 
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $input['empresa_id'] = $eid;
        $id = $service->createCertificate($input);
        echo json_encode(['success' => true, 'id' => $id]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
