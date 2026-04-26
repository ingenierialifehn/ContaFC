<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
print_r($db->query('DESCRIBE comprobantes')->fetchAll(PDO::FETCH_ASSOC));
print_r($db->query('DESCRIBE asientos')->fetchAll(PDO::FETCH_ASSOC));
