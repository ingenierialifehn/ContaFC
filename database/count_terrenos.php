<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
echo $db->query("SELECT COUNT(*) FROM asientos WHERE cuenta_id = (SELECT id FROM puc_cuentas WHERE codigo = '12010106')")->fetchColumn() . "\n";
