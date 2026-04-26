<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT proyecto_id, COUNT(*) FROM asientos WHERE YEAR((SELECT fecha FROM comprobantes WHERE id = comprobante_id)) = 2023 GROUP BY proyecto_id")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
