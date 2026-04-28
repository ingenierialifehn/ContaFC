<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();
$stmt = $db->query("SELECT id, nombre, nit FROM empresas");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
