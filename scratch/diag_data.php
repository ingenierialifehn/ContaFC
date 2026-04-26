<?php
require 'bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();

echo "--- Comprobantes por año ---\n";
$res = $db->query("SELECT YEAR(fecha) as anio, estado, COUNT(*) as cnt FROM comprobantes GROUP BY anio, estado")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);

echo "\n--- Movimientos por año (asientos) ---\n";
$res2 = $db->query("SELECT YEAR(c.fecha) as anio, SUBSTR(p.codigo,1,1) as g, COUNT(*) as cnt 
                    FROM asientos a 
                    JOIN comprobantes c ON a.comprobante_id = c.id 
                    JOIN puc_cuentas p ON a.cuenta_id = p.id
                    WHERE c.estado = 'registrado'
                    GROUP BY anio, g")->fetchAll(PDO::FETCH_ASSOC);
print_r($res2);
