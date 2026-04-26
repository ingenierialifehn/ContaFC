<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();
$stmt = $db->query("SELECT conteo, fecha FROM asientos LIMIT 10");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt2 = $db->query("SELECT COUNT(*) FROM asientos WHERE fecha IS NULL");
echo "Fechas NULL: " . $stmt2->fetchColumn() . "\n";

$stmt3 = $db->query("SELECT COUNT(*) FROM asientos WHERE conteo IS NULL");
echo "Conteo NULL: " . $stmt3->fetchColumn() . "\n";
