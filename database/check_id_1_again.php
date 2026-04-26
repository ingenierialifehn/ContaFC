<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("SELECT id, conteo, debito, credito, descripcion FROM asientos WHERE id = 1");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
