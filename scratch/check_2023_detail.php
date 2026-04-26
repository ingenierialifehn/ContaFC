<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT id, empresa_id, estado, fecha FROM comprobantes WHERE YEAR(fecha) = 2023")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
