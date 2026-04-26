<?php
require 'bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();

echo "--- Movimientos 2024 (Auxiliares de Resultados) ---\n";
$sql = "SELECT p.codigo, p.nombre, SUM(a.debito) as d, SUM(a.credito) as c, SUM(a.debito - a.credito) as neto
        FROM asientos a
        JOIN comprobantes c ON a.comprobante_id = c.id
        JOIN puc_cuentas p ON a.cuenta_id = p.id
        WHERE YEAR(c.fecha) = 2024 AND c.estado = 'registrado' AND SUBSTR(p.codigo,1,1) IN ('4','5','6','7')
        GROUP BY p.codigo, p.nombre
        HAVING ABS(neto) > 0.01";
$res = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
?>
