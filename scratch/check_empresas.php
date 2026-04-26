<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT id, nombre FROM empresas")->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($res);
echo "</pre>";
