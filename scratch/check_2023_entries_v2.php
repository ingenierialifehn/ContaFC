<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT c.id, c.numero, c.fecha, COUNT(a.id) as entries 
                  FROM comprobantes c 
                  JOIN asientos a ON c.id = a.comprobante_id 
                  WHERE YEAR(c.fecha) = 2023 AND c.estado = 'registrado'
                  GROUP BY c.id")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
