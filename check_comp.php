<?php
require 'bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("SELECT id, fecha, descripcion FROM asientos WHERE comprobante_id = 10603 AND fecha BETWEEN '2023-01-01' AND '2023-12-31'");
$comps = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($comps);
