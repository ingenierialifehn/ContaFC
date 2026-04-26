<?php
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();
$q = 'maria';

$sql = "SELECT a.fecha, c.numero, ta.razon_social as t_asiento, th.razon_social as t_comp
        FROM asientos a
        JOIN comprobantes c ON a.comprobante_id = c.id
        LEFT JOIN terceros ta ON a.tercero_id = ta.id
        LEFT JOIN terceros th ON c.tercero_id = th.id
        WHERE (LOWER(ta.razon_social) LIKE :q OR LOWER(th.razon_social) LIKE :q OR LOWER(ta.nombre_comercial) LIKE :q OR LOWER(th.nombre_comercial) LIKE :q)
        LIMIT 5";

$stmt = $db->prepare($sql);
$stmt->execute([':q' => "%$q%"]);
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Resultados para '$q':\n";
print_r($res);
