<?php
require_once __DIR__ . '/bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$permisos = json_encode([
    'dashboard' => ['r' => true],
    'reportes' => ['r' => true],
    'asiento' => ['r' => true, 'c' => true, 'u' => true, 'd' => true],
    'comprobantes' => ['r' => true],
], JSON_UNESCAPED_UNICODE);
$db->prepare("UPDATE usuarios SET permisos = ? WHERE username = 'contador3'")->execute([$permisos]);
echo "Usuario contador3 desbloqueado con permisos básicos.";
