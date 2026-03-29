<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

header('Content-Type: application/json; charset=utf-8');
Auth::requireAuth();

$db  = Database::getInstance()->getPdo();
$eid = Auth::empresaId();

$q = trim($_GET['q'] ?? '');

try {
    if (strlen($q) < 2) {
        echo json_encode(['data' => []]);
        exit;
    }

    $stmt = $db->prepare(
        "SELECT id, codigo, nombre, naturaleza, tipo_cuenta
         FROM puc_cuentas
         WHERE empresa_id = :eid
           AND acepta_movimiento = 1
           AND activa = 1
           AND (codigo LIKE :lq OR nombre LIKE :nq)
         ORDER BY codigo ASC
         LIMIT 20"
    );
    $stmt->execute([':eid' => $eid, ':lq' => "{$q}%", ':nq' => "%{$q}%"]);
    echo json_encode(['data' => $stmt->fetchAll()]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error buscando cuentas.']);
}
