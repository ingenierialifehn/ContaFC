<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;

try {
    $db = Database::getInstance()->getPdo();
    $stmt = $db->query("SELECT CONSTRAINT_NAME, UPDATE_RULE, DELETE_RULE 
                        FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS 
                        WHERE TABLE_NAME = 'asientos' AND REFERENCED_TABLE_NAME = 'comprobantes'");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
