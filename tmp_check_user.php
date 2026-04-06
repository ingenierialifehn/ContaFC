<?php
require_once __DIR__ . '/bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->prepare("SELECT id, username, permisos, rol FROM usuarios WHERE username = ?");
$stmt->execute(['contador3']);
$user = $stmt->fetch();
print_r($user);
