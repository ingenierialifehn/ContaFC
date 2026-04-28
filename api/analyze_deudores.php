<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "ANÁLISIS DE CUENTA: DEUDORES VARIOS\n\n";

// 1. Encontrar el código de la cuenta
$stmt = $db->query("SELECT id, codigo, nombre FROM puc_cuentas WHERE nombre LIKE '%DEUDORES VARIOS%' AND empresa_id = 1");
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($accounts);

if (empty($accounts)) {
    die("No se encontró la cuenta 'DEUDORES VARIOS'.");
}

$accId = $accounts[0]['id'];

// 2. Ver movimientos detallados incluyendo los comprobantes que estamos filtrando
echo "\nMOVIMIENTOS EN 2023:\n";
$stmt = $db->prepare("
    SELECT a.id, a.debito, a.credito, a.descripcion, c.id as comp_id, c.fecha, a.linea
    FROM asientos a
    JOIN comprobantes c ON a.comprobante_id = c.id
    WHERE a.cuenta_id = :acc_id AND a.empresa_id = 1
    AND YEAR(c.fecha) <= 2023
    ORDER BY c.fecha, a.debito DESC
");
$stmt->execute([':acc_id' => $accId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalConFiltro = 0;
$totalSinFiltro = 0;

foreach ($rows as $r) {
    $val = $r['debito'] - $r['credito'];
    $totalSinFiltro += $val;
    
    // Simular el filtro actual (ignorar 10631 y 10083)
    if (!in_array($r['comp_id'], [10631, 10083])) {
        $totalConFiltro += $val;
    }
    
    echo sprintf("[%d] %s | Comp: %d | Val: %s | Desc: %s\n", 
        $r['id'], $r['fecha'], $r['comp_id'], number_format($val, 2), $r['descripcion']);
}

echo "\n--- RESUMEN ---\n";
echo "Total SIN filtro: " . number_format($totalSinFiltro, 2) . "\n";
echo "Total CON filtro: " . number_format($totalConFiltro, 2) . "\n";
echo "Saldo esperado: 300,000.00\n";
?>
