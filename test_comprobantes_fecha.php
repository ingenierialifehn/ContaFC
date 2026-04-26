<?php
require 'bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("SELECT c.id, c.fecha as c_fecha, MAX(a.fecha) as a_fecha FROM comprobantes c LEFT JOIN asientos a ON c.id = a.comprobante_id GROUP BY c.id LIMIT 20");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
