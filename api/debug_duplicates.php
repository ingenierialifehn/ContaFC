<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();
$eid = 1;

echo "FIND DUPLICATES FOR BCO OCCIDENTE (11020104)\n";
$stmt = $db->prepare("
    SELECT *
    FROM asientos a
    JOIN puc_cuentas p ON a.cuenta_id = p.id
    WHERE p.codigo = '11020104' AND p.empresa_id = :eid
    AND a.comprobante_id = 9999
");
$stmt->execute([':eid' => $eid]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo "ID: {$row['id']} | Deb: {$row['debito']} | Cre: {$row['credito']} | Desc: '{$row['descripcion']}' | Created: {$row['created_at']}\n";
}
?>
