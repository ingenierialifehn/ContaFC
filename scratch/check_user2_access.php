<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT * FROM usuarios_empresas WHERE usuario_id = 2")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
