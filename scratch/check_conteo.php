<?php
require 'bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("SELECT conteo, date(fecha) as fecha, descripcion FROM asientos LIMIT 10");
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($res, JSON_PRETTY_PRINT);
