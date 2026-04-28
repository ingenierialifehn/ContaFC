<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "SEARCHING FOR 14,183,206.74 CREDIT\n";
$stmt = $db->query("
    SELECT a.id, a.credito, c.id as comp_id, c.fecha, a.descripcion
    FROM asientos a
    JOIN comprobantes c ON a.comprobante_id = c.id
    WHERE a.credito > 14000000 AND a.empresa_id = 1
");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
