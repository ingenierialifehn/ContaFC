<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
// Check account 11010102 for 2023
$sql = "SELECT a.debito, a.credito, c.fecha, c.estado
        FROM asientos a
        JOIN comprobantes c ON a.comprobante_id = c.id
        WHERE a.cuenta_id = (SELECT id FROM puc_cuentas WHERE codigo = '11010102' LIMIT 1)
          AND YEAR(c.fecha) = 2023";
$res = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
