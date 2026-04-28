<?php


ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// ─── Configuración ────────────────────────────────────────────────────────
require_once __DIR__ . '/config/app.php';

// ─── Timezone ─────────────────────────────────────────────────────────────
date_default_timezone_set(APP_TIMEZONE);

// ─── Sesión segura ────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 3600 * 8,
        'path'     => BASE_URL . '/',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// ─── Autoloader PSR-4 ─────────────────────────────────────────────────────
spl_autoload_register(function ($class) {
    $prefix = 'ContaFC\\';
    $base   = __DIR__ . '/src/';

    if (strpos($class, $prefix) !== 0) return;

    $relative = substr($class, strlen($prefix));
    $file     = $base . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
