<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
$counts = [];
foreach ($tables as $t) {
    try {
        $st = $db->query("SELECT COUNT(*) FROM `$t` ");
        $counts[$t] = $st->fetchColumn();
    } catch (Exception $e) {
        $counts[$t] = "Error: " . $e->getMessage();
    }
}
echo json_encode($counts, JSON_PRETTY_PRINT);
