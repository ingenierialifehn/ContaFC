<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "FINAL AUDIT FOR 2023 BALANCES (AFTER FIX)\n";

$accounts = ['11020104', '12010106', '11050101'];
foreach ($accounts as $code) {
    $stmt = $db->prepare("
        SELECT p.nombre, SUM(a.debito - a.credito) as total
        FROM asientos a
        JOIN puc_cuentas p ON a.cuenta_id = p.id
        JOIN comprobantes c ON a.comprobante_id = c.id
        WHERE p.codigo = :code AND p.empresa_id = 1
        AND c.estado = 'registrado'
        AND YEAR(c.fecha) <= 2023
    ");
    $stmt->execute([':code' => $code]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Account $code ({$res['nombre']}): Total = {$res['total']}\n";
}
?>
