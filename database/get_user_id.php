<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$uid = $db->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn();
echo "User ID found: " . ($uid ?: 'NONE') . "\n";
