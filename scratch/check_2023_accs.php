<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT p.empresa_id, p.activa, COUNT(*) as entries 
                  FROM asientos a 
                  JOIN comprobantes c ON a.comprobante_id = c.id 
                  JOIN puc_cuentas p ON a.cuenta_id = p.id
                  WHERE YEAR(c.fecha) = 2023 AND c.estado = 'registrado'
                  GROUP BY p.empresa_id, p.activa")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
