<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("SELECT DISTINCT YEAR(fecha) as anio FROM asientos");
echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN), JSON_PRETTY_PRINT);
