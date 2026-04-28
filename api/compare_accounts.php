<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "ANÁLISIS COMPARATIVO DE ASIENTOS (2023)\n\n";

$accounts = ['11020104' => 'Bco Occidente (MAL)', '11050101' => 'CxC Clientes (BIEN)'];

foreach ($accounts as $code => $label) {
    echo "--- $label ($code) ---\n";
    $stmt = $db->prepare("
        SELECT a.debito, a.credito, a.descripcion, c.id as comp_id, c.fecha, a.created_at
        FROM asientos a
        JOIN puc_cuentas p ON a.cuenta_id = p.id
        JOIN comprobantes c ON a.comprobante_id = c.id
        WHERE p.codigo = :code AND p.empresa_id = 1
        AND YEAR(c.fecha) = 2023
        ORDER BY c.fecha, a.debito DESC
    ");
    $stmt->execute([':code' => $code]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Agrupamos para ver duplicados
    $groups = [];
    foreach ($rows as $r) {
        $key = "{$r['fecha']}_{$r['debito']}_{$r['credito']}_" . trim($r['descripcion']);
        if (!isset($groups[$key])) $groups[$key] = 0;
        $groups[$key]++;
    }
    
    echo "Total registros: " . count($rows) . "\n";
    echo "Grupos con duplicados exactos:\n";
    foreach ($groups as $key => $count) {
        if ($count > 1) echo "  - $key: $count veces\n";
    }
    echo "\n";
}
?>
