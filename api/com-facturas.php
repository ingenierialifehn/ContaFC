<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requirePermiso('comercial');

header('Content-Type: application/json');

$db  = Database::getInstance()->getPdo();
$eid = Auth::empresaId();
$uid = Auth::userId();
$body = json_decode(file_get_contents('php://input'), true);

if (!$body || empty($body['lineas'])) {
    http_response_code(400); echo json_encode(['error' => 'Datos inválidos']); exit;
}

try {
    $db->beginTransaction();

    // 1. Obtener y Validar CAI Activo
    $stmtC = $db->prepare("SELECT * FROM com_cai WHERE empresa_id = :eid AND activo = 1 AND fecha_limite >= CURDATE() AND consecutivo_actual < rango_hasta FOR UPDATE");
    $stmtC->execute([':eid' => $eid]);
    $cai = $stmtC->fetch();
    if (!$cai) throw new Exception("No hay resolución CAI activa o rango agotado.");

    $proxCorrelativo = $cai['consecutivo_actual'] + 1;
    $numeroFactura = sprintf("%s-%s-%s-%08d", $cai['establecimiento'], $cai['punto_emision'], $cai['tipo_documento'], $proxCorrelativo);

    // 2. Calcular Totales
    $subtotal0 = 0; $subtotal15 = 0; $subtotal18 = 0;
    $isv15 = 0; $isv18 = 0; $total = 0;

    foreach ($body['lineas'] as $l) {
        $st = (float)$l['subtotal'];
        $imp = (float)$l['total_isv'];
        if ($l['isv'] == 15) { $subtotal15 += $st; $isv15 += $imp; }
        elseif ($l['isv'] == 18) { $subtotal18 += $st; $isv18 += $imp; }
        else { $subtotal0 += $st; }
    }
    $total = $subtotal0 + $subtotal15 + $subtotal18 + $isv15 + $isv18;

    // 3. Insertar Cabecera Factura
    $stmtF = $db->prepare(
        "INSERT INTO com_facturas (empresa_id, cai_id, tercero_id, tipo_pago, fecha, fecha_vence, numero_factura, 
         subtotal_0, subtotal_15, subtotal_18, isv_15, isv_18, total, estado, vendededor_id)
         VALUES (:eid, :cid, :tid, :tp, :fec, :fv, :num, :s0, :s15, :s18, :i15, :i18, :tot, 'pagada', :uid)"
    );
    // Nota: vendededor_id fix typo si aplica (revisar esquema)
    $stmtF = $db->prepare(
        "INSERT INTO com_facturas (empresa_id, cai_id, tercero_id, tipo_pago, fecha, fecha_vence, numero_factura, 
         subtotal_0, subtotal_15, subtotal_18, isv_15, isv_18, total, estado)
         VALUES (:eid, :cid, :tid, :tp, :fec, :fv, :num, :s0, :s15, :s18, :i15, :i18, :tot, 'pendiente')"
    );
    $stmtF->execute([
        ':eid' => $eid,
        ':cid' => $cai['id'],
        ':tid' => $body['cliente_id'],
        ':tp'  => $body['tipo_pago'],
        ':fec' => $body['fecha'],
        ':fv'  => $body['fecha'], 
        ':num' => $numeroFactura,
        ':s0'  => $subtotal0,
        ':s15' => $subtotal15,
        ':s18' => $subtotal18,
        ':i15' => $isv15,
        ':i18' => $isv18,
        ':tot' => $total
    ]);
    $facturaId = $db->lastInsertId();

    // 4. Insertar Detalles y Descontar Stock
    $stmtD = $db->prepare(
        "INSERT INTO com_facturas_detalle (factura_id, producto_id, cantidad, precio_unitario, tasa_isv, total_isv, total_linea)
         VALUES (:fid, :pid, :can, :pre, :tisv, :tiv, :tl)"
    );
    $stmtStock = $db->prepare("UPDATE com_trazabilidad SET stock_actual = stock_actual - :can WHERE id = :tid AND empresa_id = :eid");

    foreach ($body['lineas'] as $l) {
        $stmtD->execute([
            ':fid' => $facturaId,
            ':pid' => $l['p_id'],
            ':can' => $l['cant'],
            ':pre' => $l['precio'],
            ':tisv' => $l['isv'],
            ':tiv' => $l['total_isv'],
            ':tl' => $l['subtotal'] + $l['total_isv']
        ]);

        if (!empty($l['traza_id'])) {
            $stmtStock->execute([':can' => $l['cant'], ':tid' => $l['traza_id'], ':eid' => $eid]);
        }
    }

    // 5. Actualizar CAI
    $db->prepare("UPDATE com_cai SET consecutivo_actual = :ca WHERE id = :id")
       ->execute([':ca' => $proxCorrelativo, ':id' => $cai['id']]);

    // 6. GENERAR ASIENTO CONTABLE AUTOMÁTICO (CONEXIÓN CONTABLE)
    // Buscamos tipo de comprobante 'Factura Venta' (FVE)
    $stmtT = $db->prepare("SELECT id FROM tipos_comprobante WHERE empresa_id = :eid AND codigo = 'FVE' LIMIT 1");
    $stmtT->execute([':eid' => $eid]);
    $tipoId = $stmtT->fetchColumn() ?: 1;

    $stmtComp = $db->prepare(
        "INSERT INTO comprobantes (empresa_id, tipo_id, numero, fecha, tercero_id, observaciones, usuario_id, estado)
         VALUES (:eid, :tid, :num, :fec, :ter, :obs, :uid, 'registrado')"
    );
    $stmtComp->execute([
        ':eid' => $eid,
        ':tid' => $tipoId,
        ':num' => $proxCorrelativo,
        ':fec' => $body['fecha'],
        ':ter' => $body['cliente_id'],
        ':obs' => "Factura SAR: $numeroFactura",
        ':uid' => $uid
    ]);
    $compId = $db->lastInsertId();

    // Líneas del asiento (Cuentas fijas para ejemplo, en producción se sacan del producto/empresa)
    $asientos = [];
    $ctaXC = '110201'; // Clientes Nacionales
    $ctaVenta = '410101'; // Ventas Gravadas
    $ctaISV = '210301'; // ISV Cobrado

    // Débito Cliente (Total)
    $asientos[] = ['cta' => $ctaXC, 'deb' => $total, 'cre' => 0, 'des' => "Venta Fact $numeroFactura"];
    // Crédito Venta (Base)
    $asientos[] = ['cta' => $ctaVenta, 'deb' => 0, 'cre' => $subtotal15 + $subtotal18 + $subtotal0, 'des' => "Ingreso Fact $numeroFactura"];
    // Crédito ISV
    if ($isv15 + $isv18 > 0) {
        $asientos[] = ['cta' => $ctaISV, 'deb' => 0, 'cre' => $isv15 + $isv18, 'des' => "ISV Fact $numeroFactura"];
    }

    $stmtAs = $db->prepare(
        "INSERT INTO asientos (comprobante_id, cuenta_id, debito, credito, tercero_id, descripcion, fecha)
         SELECT :cid, id, :deb, :cre, :ter, :des, :fec FROM puc_cuentas WHERE codigo = :cod AND empresa_id = :eid LIMIT 1"
    );

    foreach ($asientos as $a) {
        $stmtAs->execute([
            ':cid' => $compId,
            ':deb' => $a['deb'],
            ':cre' => $a['cre'],
            ':ter' => $body['cliente_id'],
            ':des' => $a['des'],
            ':fec' => $body['fecha'],
            ':cod' => $a['cta'],
            ':eid' => $eid
        ]);
    }

    $db->commit();
    echo json_encode(['success' => true, 'numero' => $numeroFactura, 'factura_id' => $facturaId]);
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
