<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');

echo "Analizando desajuste de fechas entre asientos y comprobantes...\n";
$sql = "SELECT c.id, c.fecha as fecha_comprobante, MIN(a.fecha) as fecha_asiento, COUNT(*) as total_asientos
        FROM comprobantes c
        JOIN asientos a ON a.comprobante_id = c.id
        GROUP BY c.id
        HAVING c.fecha <> MIN(a.fecha)";
$stmt = $db->query($sql);
$mismatches = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total comprobantes con fecha incorrecta: " . count($mismatches) . "\n";
if (count($mismatches) > 0) {
    echo "Ejemplo: Comprobante ID " . $mismatches[0]['id'] . " tiene fecha " . $mismatches[0]['fecha_comprobante'] . " pero sus asientos son de " . $mismatches[0]['fecha_asiento'] . "\n";
}
