<?php
require 'bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
print_r($db->query('DESCRIBE asientos')->fetchAll(PDO::FETCH_ASSOC));
