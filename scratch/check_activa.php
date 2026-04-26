<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT activa, COUNT(*) FROM puc_cuentas GROUP BY activa")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
