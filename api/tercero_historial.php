<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

header('Content-Type: application/json; charset=utf-8');
Auth::requireAuth();

$db  = Database::getInstance()->getPdo();
$eid = Auth::empresaId();

try {
    $terceroId = isset($_GET['tercero_id']) ? (int)$_GET['tercero_id'] : null;
    $q = trim($_GET['q'] ?? '');

    // Log para depuración
    file_put_contents(__DIR__ . '/../tmp/debug_historial.json', json_encode(['tid' => $terceroId, 'q' => $q, 'eid' => $eid]));

    if (!$terceroId && !$q) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $sql = "SELECT a.fecha, tc.codigo AS tipo_codigo, c.numero, cu.codigo AS cuenta_codigo, 
                   a.descripcion, a.debito, a.credito, 
                   COALESCE(ta.razon_social, th.razon_social) AS tercero_nombre
            FROM asientos a
            JOIN comprobantes c ON a.comprobante_id = c.id
            JOIN tipos_comprobante tc ON c.tipo_comp_id = tc.id
            LEFT JOIN puc_cuentas cu ON a.cuenta_id = cu.id
            LEFT JOIN terceros ta ON a.tercero_id = ta.id
            LEFT JOIN terceros th ON c.tercero_id = th.id
            WHERE a.empresa_id = :eid";
    
    $params = [':eid' => $eid];

    if ($terceroId && $terceroId > 0) {
        if ($q) {
            // Si tenemos ambos, buscamos por ID o por Nombre/Descripción
            $sql .= " AND (a.tercero_id = :tid OR c.tercero_id = :tid 
                           OR LOWER(ta.razon_social) LIKE :q OR LOWER(th.razon_social) LIKE :q
                           OR LOWER(a.descripcion) LIKE :q)";
            $params[':tid'] = $terceroId;
            $params[':q'] = "%" . strtolower($q) . "%";
        } else {
            $sql .= " AND (a.tercero_id = :tid OR c.tercero_id = :tid)";
            $params[':tid'] = $terceroId;
        }
    } else {
        $cleanQ = preg_replace('/^[0-9\s\-–—]+/', '', $q);
        $sql .= " AND (
            LOWER(ta.razon_social) LIKE :q 
            OR LOWER(th.razon_social) LIKE :q 
            OR ta.nit_cc LIKE :q 
            OR th.nit_cc LIKE :q
            OR LOWER(a.descripcion) LIKE :q
        )";
        $params[':q'] = "%" . strtolower($cleanQ) . "%";
    }

    $sql .= " ORDER BY a.fecha DESC, c.id DESC LIMIT 100";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $data]);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
