<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT COUNT(*) FROM puc_cuentas WHERE SUBSTR(codigo,1,1) IN ('4','5','6')")->fetchColumn();
echo "Total accounts 4,5,6: $res\n";
