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

try {
    $db->beginTransaction();

    // 1. Obtener Contratos Pendientes de este Mes
    // Donde dia_facturacion <= HOY y ultima_factura no es de este MES/AÑO
    $hoy = date('Y-m-d');
    $anioMes = date('Y-m');
    
    $stmtProc = $db->prepare(
        "SELECT c.*, p.tasa_isv, p.nombre as nombre_prod, p.precio_venta 
         FROM com_contratos c
         JOIN com_productos p ON c.producto_id = p.id
         WHERE c.empresa_id = :eid AND c.activa = 1
         AND (c.ultima_factura IS NULL OR DATE_FORMAT(c.ultima_factura, '%Y-%m') < :am)
         AND c.dia_facturacion <= :dia"
    );
    $stmtProc->execute([':eid' => $eid, ':am' => $anioMes, ':dia' => date('j')]);
    $pendientes = $stmtProc->fetchAll(PDO::FETCH_ASSOC);

    if (empty($pendientes)) {
        echo json_encode(['success' => true, 'count' => 0, 'msg' => 'No hay contratos pendientes hoy']);
        $db->rollBack();
        exit;
    }

    $count = 0;
    foreach ($pendientes as $c) {
        // --- LOGICA DE FACTURACION (Copiada de com-facturas.php) ---
        // Obtener CAI
        $stC = $db->prepare("SELECT * FROM com_cai WHERE empresa_id = :eid AND activo = 1 AND fecha_limite >= CURDATE() AND consecutivo_actual < rango_hasta FOR UPDATE");
        $stC->execute([':eid' => $eid]);
        $cai = $stC->fetch();
        if (!$cai) break; // Detener si se acaba el rango fiscal

        $prox = $cai['consecutivo_actual'] + 1;
        $num = sprintf("%s-%s-%s-%08d", $cai['establecimiento'], $cai['punto_emision'], $cai['tipo_documento'], $prox);

        $montoBase = (float)$c['monto'];
        $isvTasa = (float)$c['tasa_isv'];
        $montoIsv = $montoBase * ($isvTasa / 100);
        $total = $montoBase + $montoIsv;

        // Insertar Factura
        $stF = $db->prepare("INSERT INTO com_facturas (empresa_id, cai_id, tercero_id, tipo_pago, fecha, fecha_vence, numero_factura, subtotal_0, subtotal_15, subtotal_18, isv_15, isv_18, total, estado) VALUES (:eid, :cid, :tid, 'contado', :fec, :fec, :num, :s0, :s15, :s18, :i15, :i18, :tot, 'pendiente')");
        $stF->execute([
            ':eid' => $eid, ':cid' => $cai['id'], ':tid' => $c['cliente_id'], ':fec' => $hoy, ':num' => $num,
            ':s0' => ($isvTasa == 0 ? $montoBase : 0), ':s15' => ($isvTasa == 15 ? $montoBase : 0), ':s18' => ($isvTasa == 18 ? $montoBase : 0),
            ':i15' => ($isvTasa == 15 ? $montoIsv : 0), ':i18' => ($isvTasa == 18 ? $montoIsv : 0), ':tot' => $total
        ]);
        $fid = $db->lastInsertId();

        // Detalle
        $db->prepare("INSERT INTO com_facturas_detalle (factura_id, producto_id, cantidad, precio_unitario, tasa_isv, total_isv, total_linea) VALUES (:fid, :pid, 1, :pre, :tisv, :tiv, :tl)")
           ->execute([':fid' => $fid, ':pid' => $c['producto_id'], ':pre' => $montoBase, ':tisv' => $isvTasa, ':tiv' => $montoIsv, ':tl' => $total]);

        // Actualizar CAI y Contrato
        $db->prepare("UPDATE com_cai SET consecutivo_actual = :ca WHERE id = :id")->execute([':ca' => $prox, ':id' => $cai['id']]);
        $db->prepare("UPDATE com_contratos SET ultima_factura = :hoy WHERE id = :id")->execute([':hoy' => $hoy, ':id' => $c['id']]);

        // GENERAR ASIENTO (Simplificado para masivo)
        $stComp = $db->prepare("INSERT INTO comprobantes (empresa_id, tipo_id, numero, fecha, tercero_id, observaciones, usuario_id, estado) VALUES (:eid, 1, :num, :fec, :ter, :obs, :uid, 'registrado')");
        $stComp->execute([':eid' => $eid, ':num' => $prox, ':fec' => $hoy, ':ter' => $c['cliente_id'], ':obs' => "FACTURACION MASIVA CONTRATO #{$c['id']}", ':uid' => $uid]);

        $count++;
    }

    $db->commit();
    echo json_encode(['success' => true, 'count' => $count]);

} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
}
