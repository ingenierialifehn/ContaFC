<?php
try {
    $db = new PDO("mysql:host=172.18.0.3;dbname=contafc", "root", "root");
    $stmt = $db->query("
        SELECT a.descripcion, a.debito, a.credito, a.cuenta_id, p.codigo as account_code, t.razon_social as name_found
        FROM asientos a
        JOIN comprobantes c ON a.comprobante_id = c.id
        JOIN puc_cuentas p ON a.cuenta_id = p.id
        LEFT JOIN terceros t ON a.tercero_id = t.id
        WHERE c.created_at = '2026-04-05 11:54:24'
        LIMIT 100
    ");
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($res, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo $e->getMessage();
}
