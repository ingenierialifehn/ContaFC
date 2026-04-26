<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;

try {
    $db = Database::getInstance()->getPdo();
    $ids = [10001, 10002, 10004, 10007, 10009];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("SELECT id, numero, fecha, observaciones FROM comprobantes WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo $e->getMessage();
}
