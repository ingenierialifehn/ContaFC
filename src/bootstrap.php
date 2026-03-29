<?php
declare(strict_types=1);

/**
 * Bootstrap – punto de entrada para todas las páginas web.
 * Incluir con require_once en cada archivo PHP.
 */

// ─── Carga de variables de entorno (desde $_ENV del entorno Docker) ────────
// Si ejecutas fuera de Docker, puedes usar un loader de .env:
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// ─── Timezone ────────────────────────────────────────────────────────────
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Bogota');

// ─── Sesión segura ────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 3600 * 8,   // 8 horas
        'path'     => '/',
        'secure'   => false,      // true en producción con HTTPS
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// ─── Autoload ─────────────────────────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    $prefix = 'ContaFC\\';
    $base   = __DIR__ . '/';

    if (!str_starts_with($class, $prefix)) return;

    $relative = substr($class, strlen($prefix));
    $file     = $base . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
