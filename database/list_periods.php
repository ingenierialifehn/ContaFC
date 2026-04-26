<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
print_r($db->query('SELECT * FROM periodos')->fetchAll(PDO::FETCH_ASSOC));
