<?php
require 'bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("SELECT id, conteo, descripcion FROM asientos WHERE DATE(fecha) = '2023-07-18' LIMIT 5");
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($res, JSON_PRETTY_PRINT);
