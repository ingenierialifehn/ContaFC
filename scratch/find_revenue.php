<?php
require 'bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();

echo "--- BUSQUEDA DE INGRESOS (GRUPO 4) ---\n";
$sql = "SELECT YEAR(c.fecha) as anio, MONTH(c.fecha) as mes, SUM(a.credito - a.debito) as total_ingresos
        FROM asientos a
        JOIN comprobantes c ON a.comprobante_id = c.id
        JOIN puc_cuentas p ON a.cuenta_id = p.id
        WHERE SUBSTR(p.codigo,1,1) = '4' AND c.estado = 'registrado'
        GROUP BY anio, mes
        ORDER BY anio DESC, mes DESC";
$res = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
print_r($res);

echo "\n--- BUSQUEDA POR PROYECTO (2024) ---\n";
$sql2 = "SELECT py.nombre as proyecto, SUM(a.credito - a.debito) as total_ingresos
         FROM asientos a
         JOIN comprobantes c ON a.comprobante_id = c.id
         JOIN puc_cuentas p ON a.cuenta_id = p.id
         LEFT JOIN proyectos py ON a.proyecto_id = py.id
         WHERE YEAR(c.fecha) = 2024 AND SUBSTR(p.codigo,1,1) = '4' AND c.estado = 'registrado'
         GROUP BY proyecto";
$res2 = $db->query($sql2)->fetchAll(PDO::FETCH_ASSOC);
print_r($res2);
?>
