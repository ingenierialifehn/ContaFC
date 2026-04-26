<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT c.id, c.fecha, COUNT(a.id) as entries 
                   FROM comprobantes c 
                   LEFT JOIN asientos a ON c.id = a.comprobante_id 
                   WHERE YEAR(c.fecha) = 2023 
                   GROUP BY c.id")->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($res);
echo "</pre>";

$res2 = $db->query("SELECT COUNT(*) FROM asientos WHERE empresa_id = 1")->fetchColumn();
echo "Total asientos: $res2\n";
