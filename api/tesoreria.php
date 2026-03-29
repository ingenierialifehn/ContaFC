<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;
use ContaFC\Services\TreasuryService;

Auth::requireAuth();

header('Content-Type: application/json');

$db = Database::getInstance()->getPdo();
$service = new TreasuryService();
$method  = $_SERVER['REQUEST_METHOD'];
$eid     = Auth::empresaId();

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'bancos';
        if ($action === 'bancos') {
            $stmt = $db->prepare("SELECT b.*, c.codigo as cta_cod, c.nombre as cta_nom FROM bancos_cuentas b JOIN puc_cuentas c ON b.cuenta_id = c.id WHERE b.empresa_id = :eid");
            $stmt->execute([':eid' => $eid]);
            echo json_encode(['data' => $stmt->fetchAll()]);
        }
        elseif ($action === 'recurrentes') {
            $stmt = $db->prepare("SELECT * FROM comprobantes_recurrentes WHERE empresa_id = :eid");
            $stmt->execute([':eid' => $eid]);
            echo json_encode(['data' => $stmt->fetchAll()]);
        }
    } 
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        if ($action === 'add_banco') {
            $stmt = $db->prepare("INSERT INTO bancos_cuentas (empresa_id, nombre, numero_cuenta, moneda, cuenta_id, saldo_inicial) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$eid, $input['nombre'], $input['numero_cuenta'], $input['moneda'], $input['cuenta_id'], $input['saldo_inicial'] ?? 0]);
            echo json_encode(['success' => true]);
        }
        elseif ($action === 'add_recurrente') {
             $stmt = $db->prepare("INSERT INTO comprobantes_recurrentes (empresa_id, nombre, frecuencia, dia_ejecucion, json_data) VALUES (?, ?, ?, ?, ?)");
             $stmt->execute([$eid, $input['nombre'], $input['frecuencia'], $input['dia_ejecucion'], json_encode($input['json_data'])]);
             echo json_encode(['success' => true]);
        }
        elseif ($action === 'process_recurrentes') {
            $periodoId = (int)$input['periodo_id'];
            $count = $service->processRecurrents($eid, $periodoId, Auth::userId());
            echo json_encode(['success' => true, 'count' => $count]);
        }
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
