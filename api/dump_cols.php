<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();
$q = $db->query("SHOW COLUMNS FROM comprobantes");
$cols = $q->fetchAll(PDO::FETCH_ASSOC);
file_put_contents(__DIR__ . '/../../scratch/cols_comprobantes.txt', print_r($cols, true));
?>
