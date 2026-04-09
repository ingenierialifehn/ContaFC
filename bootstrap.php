<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// ─── Cargar variables de entorno desde .env ──────────────────────────────────
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (!$line || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
        
        list($name, $value) = explode('=', $line, 2);
        $name  = trim($name);
        $value = trim($value);
        
        // Manejar comillas simples o dobles
        if (preg_match('/^"(.+)"$/', $value, $matches) || preg_match("/^'(.+)'$/", $value, $matches)) {
            $value = $matches[1];
        }
        
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

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
spl_autoload_register(function (string $class): void {
    $prefix = 'ContaFC\\';
    $base   = __DIR__ . '/src/';

    if (!str_starts_with($class, $prefix)) return;

    $relative = substr($class, strlen($prefix));
    $file     = $base . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
