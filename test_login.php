<?php
require_once __DIR__ . '/bootstrap.php';
$username = 'admin';
$password = 'Admin2026!';
$empresa_id = 1;

$db = ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->prepare("SELECT id, username, password_hash, activo FROM usuarios WHERE empresa_id = :eid AND username = :user");
$stmt->execute([':eid' => $empresa_id, ':user' => $username]);
$user = $stmt->fetch();

echo "User found: " . ($user ? 'YES' : 'NO') . "\n";
echo "Activo: " . ($user ? $user['activo'] : 'N/A') . "\n";
if ($user) {
    echo "Hash: " . $user['password_hash'] . "\n";
    echo "Verify: " . (password_verify($password, $user['password_hash']) ? 'TRUE' : 'FALSE') . "\n";
}
