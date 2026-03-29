<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
header('Content-Type: application/json');

$db  = Database::getInstance()->getPdo();
$eid = Auth::empresaId();
$uid = Auth::userId();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $stmt = $db->prepare(
            "SELECT d.*, f.numero_factura, t.razon_social 
             FROM com_devoluciones d
             JOIN com_facturas f ON d.factura_id = f.id
             JOIN terceros t ON f.tercero_id = t.id
             WHERE d.empresa_id = :eid ORDER BY d.id DESC"
        );
        $stmt->execute([':eid' => $eid]);
        echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } 
    elseif ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true);
        $fid  = (int)($body['factura_id'] ?? 0);
        
        $db->beginTransaction();

        // 1. Obtener Factura y Detalle
        $stmtF = $db->prepare("SELECT * FROM com_facturas WHERE id = :id AND empresa_id = :eid");
        $stmtF->execute([':id' => $fid, ':eid' => $eid]);
        $fact = $stmtF->fetch();
        if (!$fact) throw new Exception("Factura origen no encontrada.");

        // 2. Reingresar Stock de Trazabilidad
        $stmtD = $db->prepare("SELECT producto_id, cantidad FROM com_facturas_detalle WHERE factura_id = :fid");
        $stmtD->execute([':fid' => $fid]);
        $detalles = $stmtD->fetchAll();

        // **NOTA**: En una NC total, reingresamos todo el stock que se descontó. 
        // Idealmente rastreamos el traza_id original. (Implementación simplificada para ejemplo total)
        $db->prepare("UPDATE com_trazabilidad t 
                      JOIN com_facturas_detalle fd ON t.producto_id = fd.producto_id 
                      SET t.stock_actual = t.stock_actual + fd.cantidad
                      WHERE fd.factura_id = :fid")->execute([':fid' => $fid]);

        // 3. Registrar Nota de Crédito Comercial
        $stmtNC = $db->prepare(
            "INSERT INTO com_devoluciones (empresa_id, factura_id, fecha, motivo, total_nc)
             VALUES (:eid, :fid, :fec, :mot, :tot)"
        );
        $stmtNC->execute([
            ':eid' => $eid,
            ':fid' => $fid,
            ':fec' => $body['fecha'],
            ':mot' => $body['motivo'],
            ':tot' => $fact['total']
        ]);
        $ncId = $db->lastInsertId();

        // 4. GENERAR CONTRA-ASIENTO CONTABLE (ANULACIÓN/REVERSIÓN)
        // Buscamos tipo 'NCR' (Nota Crédito)
        $stmtT = $db->prepare("SELECT id FROM tipos_comprobante WHERE empresa_id = :eid AND codigo = 'NCR' LIMIT 1");
        $stmtT->execute([':eid' => $eid]);
        $tipoId = $stmtT->fetchColumn() ?: 2;

        $stmtComp = $db->prepare(
            "INSERT INTO comprobantes (empresa_id, tipo_id, numero, fecha, tercero_id, observaciones, usuario_id, estado)
             VALUES (:eid, :tid, 0, :fec, :ter, :obs, :uid, 'registrado')"
        );
        $stmtComp->execute([
            ':eid' => $eid,
            ':tid' => $tipoId,
            ':fec' => $body['fecha'],
            ':ter' => $fact['tercero_id'],
            ':obs' => "Reversión Factura {$fact['numero_factura']} (Devolución #$ncId)",
            ':uid' => $uid
        ]);
        $compId = $db->lastInsertId();

        // REVERSIÓN PARTIDA DOBLE:
        // Débito: Ingresos/Ventas y ISV
        // Crédito: Clientes (CXC)
        $ctaXC = '110201'; // Clientes
        $ctaVenta = '410101'; 
        $ctaISV = '210301';

        $stmtAs = $db->prepare(
            "INSERT INTO asientos (comprobante_id, cuenta_id, debito, credito, tercero_id, descripcion, fecha)
             SELECT :cid, id, :deb, :cre, :ter, :des, :fec FROM puc_cuentas WHERE codigo = :cod AND empresa_id = :eid LIMIT 1"
        );

        // Débito Venta (Base)
        $stmtAs->execute([':cid' => $compId, ':deb' => $fact['subtotal_15'] + $fact['subtotal_18'] + $fact['subtotal_0'], ':cre' => 0, ':ter' => $fact['tercero_id'], ':des' => "Devolución Fact {$fact['numero_factura']}", ':fec' => $body['fecha'], ':cod' => $ctaVenta, ':eid' => $eid]);
        // Débito ISV (Impuestos)
        if ($fact['isv_15'] + $fact['isv_18'] > 0) {
            $stmtAs->execute([':cid' => $compId, ':deb' => $fact['isv_15'] + $fact['isv_18'], ':cre' => 0, ':ter' => $fact['tercero_id'], ':des' => "Reversión ISV Fact {$fact['numero_factura']}", ':fec' => $body['fecha'], ':cod' => $ctaISV, ':eid' => $eid]);
        }
        // Crédito Clientes (Total)
        $stmtAs->execute([':cid' => $compId, ':deb' => 0, ':cre' => $fact['total'], ':ter' => $fact['tercero_id'], ':des' => "Abono NC por Devolución Fact {$fact['numero_factura']}", ':fec' => $body['fecha'], ':cod' => $ctaXC, ':eid' => $eid]);

        $db->prepare("UPDATE com_devoluciones SET comprobante_id = :cid WHERE id = :id")->execute([':cid' => $compId, ':id' => $ncId]);
        
        // Marcar factura como Anulada/Devuelta
        $db->prepare("UPDATE com_facturas SET estado = 'anulada' WHERE id = :id")->execute([':id' => $fid]);

        $db->commit();
        echo json_encode(['success' => true]);
    }
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
}
