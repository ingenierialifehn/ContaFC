<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
header('Content-Type: application/json');

$db = Database::getInstance()->getPdo();
$eid = Auth::empresaId();
$q = $_GET['q'] ?? '';

try {
    $stmt = $db->prepare(
        "SELECT f.id, f.numero_factura, t.razon_social, f.total 
         FROM com_facturas f
         JOIN terceros t ON f.tercero_id = t.id
         WHERE f.empresa_id = :eid AND (f.numero_factura LIKE :q OR t.razon_social LIKE :q)
         LIMIT 10"
    );
    $stmt->execute([':eid' => $eid, ':q' => "%$q%"]);
    echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
}
