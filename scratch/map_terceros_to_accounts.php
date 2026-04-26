<?php
try {
    $db = new PDO("mysql:host=172.18.0.3;dbname=contafc", "root", "root");
    $stmt = $db->query("
        SELECT a.tercero_id, t.razon_social, a.cuenta_id, p.codigo as account_code, p.nombre as account_name
        FROM asientos a
        JOIN terceros t ON a.tercero_id = t.id
        JOIN puc_cuentas p ON a.cuenta_id = p.id
        WHERE a.tercero_id BETWEEN 1018 AND 1052
        LIMIT 50
    ");
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($res, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo $e->getMessage();
}
