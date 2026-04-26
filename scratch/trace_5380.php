<?php
require_once __DIR__ . '/bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();

$conteo = 5380; // The 976k credit
$sql = "
    SELECT a.id, a.debito, a.credito, a.fecha, c.fecha as fecha_comp, c.observaciones, c.estado, p.codigo
    FROM asientos a
    JOIN comprobantes c ON a.comprobante_id = c.id
    JOIN puc_cuentas p ON a.cuenta_id = p.id
    WHERE a.conteo = :c
";

$st = $db->prepare($sql);
$st->execute(['c' => $conteo]);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

echo "Detalle para CONTEO $conteo:\n";
foreach ($rows as $r) {
    print_r($r);
}
