<?php
require_once __DIR__ . '/bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();

$empId = 1;
$sql = "
    SELECT a.id, a.debito, a.credito, a.descripcion, a.conteo, COALESCE(a.fecha, c.fecha) as fecha_fin
    FROM asientos a
    JOIN comprobantes c ON a.comprobante_id = c.id
    JOIN puc_cuentas p ON a.cuenta_id = p.id
    WHERE p.codigo = '11050101' AND a.empresa_id = :e 
      AND YEAR(COALESCE(a.fecha, c.fecha)) = 2023
    LIMIT 20
";

$st = $db->prepare($sql);
$st->execute(['e' => $empId]);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

echo "Muestra de asientos en DB 2023 para 11050101:\n";
foreach ($rows as $r) {
    printf("ID: %d | Cnteo: %s | D: %.2f | C: %.2f | Neto: %.2f | %s\n", 
           $r['id'], $r['conteo'], $r['debito'], $r['credito'], ($r['debito'] - $r['credito']), $r['descripcion']);
}
