<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();
$stmt = $db->query("SELECT id, conteo, fecha, debito, credito, descripcion FROM asientos LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
