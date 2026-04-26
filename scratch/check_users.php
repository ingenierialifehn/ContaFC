<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();

$users = $db->query("SELECT id, username, empresa_id FROM usuarios")->fetchAll(PDO::FETCH_ASSOC);
echo "USERS:\n";
foreach ($users as $u) {
    echo "ID: {$u['id']}, Username: {$u['username']}, Empresa ID: {$u['empresa_id']}\n";
}
