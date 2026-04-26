<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT estado, COUNT(*) FROM comprobantes WHERE YEAR(fecha) = 2023 GROUP BY estado")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
