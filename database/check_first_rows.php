<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("SELECT id, conteo, debito, credito, descripcion FROM asientos LIMIT 20");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
