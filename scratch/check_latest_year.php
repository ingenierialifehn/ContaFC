<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT MAX(YEAR(fecha)) FROM comprobantes")->fetchColumn();
echo "Latest year with data: $res\n";
