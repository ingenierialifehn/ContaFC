<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requirePermiso('asiento');

header('Content-Type: application/json');

$db  = Database::getInstance()->getPdo();
$eid = Auth::empresaId();
$uid = Auth::userId();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body || empty($body['lineas'])) throw new Exception("Datos incompletos.");

        $db->beginTransaction();

        // 1. Crear el Comprobante
        $stmtComp = $db->prepare(
            "INSERT INTO comprobantes (empresa_id, tipo_id, numero, fecha, tercero_id, observaciones, usuario_id, estado)
             VALUES (:eid, :tid, :num, :fec, :ter, :obs, :uid, 'registrado')"
        );
        
        // Generar número automático (ejemplo simple: max+1 por tipo y empresa)
        $numStmt = $db->prepare("SELECT COALESCE(MAX(numero), 0) + 1 FROM comprobantes WHERE empresa_id = :eid AND tipo_id = :tid");
        $numStmt->execute([':eid' => $eid, ':tid' => $body['tipo_comp_id']]);
        $numero = $numStmt->fetchColumn();

        $stmtComp->execute([
            ':eid' => $eid,
            ':tid' => $body['tipo_comp_id'],
            ':num' => $numero,
            ':fec' => $body['fecha'],
            ':ter' => $body['tercero_id'] ?: null,
            ':obs' => $body['observaciones'] ?: null,
            ':uid' => $uid
        ]);
        $compId = $db->lastInsertId();

        // 2. Insertar Líneas de Asiento
        $stmtLinea = $db->prepare(
            "INSERT INTO asientos (comprobante_id, cuenta_id, debito, credito, tercero_id, descripcion, documento_referencia, fecha)
             VALUES (:cid, :cta, :deb, :cre, :ter, :des, :ref, :fec)"
        );

        foreach ($body['lineas'] as $l) {
            $stmtLinea->execute([
                ':cid' => $compId,
                ':cta' => $l['cuenta_id'],
                ':deb' => $l['debito'],
                ':cre' => $l['credito'],
                ':ter' => $l['tercero_id'] ?: null,
                ':des' => $l['descripcion'] ?: null,
                ':ref' => $l['documento_referencia'] ?? null,
                ':fec' => $body['fecha']
            ]);
            
            // 3. Afectar Cuentas por Cobrar/Pagar si aplica (Opcional en esta fase, pero planeado)
            // 4. Actualizar Saldos de Cuenta (Optimización para reportes)
            actualizarSaldoCuenta($db, (int)$l['cuenta_id'], (float)$l['debito'], (float)$l['credito'], $body['fecha']);
        }

        $db->commit();
        echo json_encode(['success' => true, 'comprobante_id' => $compId, 'numero' => $numero]);
    } 
    elseif ($method === 'GET') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if ($id) {
            $stmt = $db->prepare(
                "SELECT c.*, t.nombre as tercero_nombre, tc.nombre as tipo_nombre, tc.codigo as tipo_codigo
                 FROM comprobantes c
                 LEFT JOIN terceros t ON c.tercero_id = t.id
                 JOIN tipos_comprobante tc ON c.tipo_id = tc.id
                 WHERE c.id = :id AND c.empresa_id = :eid"
            );
            $stmt->execute([':id' => $id, ':eid' => $eid]);
            $comp = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$comp) throw new Exception("Comprobante no encontrado.");

            $stmtL = $db->prepare(
                "SELECT a.*, cu.codigo as cuenta_codigo, cu.nombre as cuenta_nombre, t.nombre as tercero_nombre
                 FROM asientos a
                 JOIN puc_cuentas cu ON a.cuenta_id = cu.id
                 LEFT JOIN terceros t ON a.tercero_id = t.id
                 WHERE a.comprobante_id = :cid ORDER BY a.id ASC"
            );
            $stmtL->execute([':cid' => $id]);
            $comp['lineas'] = $stmtL->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($comp);
        } else {
            // Listado de comprobantes
            $desde = $_GET['desde'] ?? date('Y-m-01');
            $hasta = $_GET['hasta'] ?? date('Y-m-d');
            $estado= $_GET['estado'] ?? 'registrado';
            
            $stmt = $db->prepare(
                "SELECT c.*, tc.codigo as tipo_comp, tc.nombre as tipo_nombre, t.nombre as tercero,
                        (SELECT SUM(debito) FROM asientos WHERE comprobante_id = c.id) as total_debitos,
                        (SELECT SUM(credito) FROM asientos WHERE comprobante_id = c.id) as total_creditos
                 FROM comprobantes c
                 JOIN tipos_comprobante tc ON c.tipo_id = tc.id
                 LEFT JOIN terceros t ON c.tercero_id = t.id
                 WHERE c.empresa_id = :eid AND c.fecha BETWEEN :d AND :h AND c.estado = :e
                 ORDER BY c.fecha DESC, c.numero DESC"
            );
            $stmt->execute([':eid' => $eid, ':d' => $desde, ':h' => $hasta, ':e' => $estado]);
            echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
    }
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Actualiza la tabla de saldos_periodo para reportes veloces (Balance/Estado Resultados)
 */
function actualizarSaldoCuenta($db, int $cuentaId, float $debito, float $credito, string $fecha) {
    $anio = (int)date('Y', strtotime($fecha));
    $mes  = (int)date('m', strtotime($fecha));
    
    // Upsert saldo del periodo
    $stmt = $db->prepare(
        "INSERT INTO saldos_periodo (cuenta_id, anio, mes, debito, credito)
         VALUES (:cta, :anio, :mes, :deb, :cre)
         ON DUPLICATE KEY UPDATE debito = debito + :deb, credito = credito + :cre"
    );
    $stmt->execute([':cta' => $cuentaId, ':anio' => $anio, ':mes' => $mes, ':deb' => $debito, ':cre' => $credito]);
}
