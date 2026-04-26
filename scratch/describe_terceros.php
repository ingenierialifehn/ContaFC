<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("DESCRIBE terceros");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['Field']}\n";
}
