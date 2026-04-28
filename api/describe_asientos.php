<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();
$q = $db->query("DESCRIBE asientos");
echo "<pre>";
print_r($q->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>
