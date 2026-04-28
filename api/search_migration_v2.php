<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "VOUCHERS WITH 'SALDO SEGUN LIBROS' (FIXED QUERY)\n";
$stmt = $db->query("
    SELECT DISTINCT c.id, c.fecha, a.descripcion
    FROM comprobantes c
    JOIN asientos a ON a.comprobante_id = c.id
    WHERE a.descripcion LIKE '%SALDO SEGUN LIBROS%' AND c.empresa_id = 1
");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
