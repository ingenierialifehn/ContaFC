<?php
// ═══════════════════════════════════════════════════════════════════════════
// ContaFC – Configuración Central
// Edite este archivo con los datos de su servidor
// ═══════════════════════════════════════════════════════════════════════════
declare(strict_types=1);

// ─── Base URL (sin barra final) ───────────────────────────────────────────
// Si el sistema corre en http://localhost:8080/contafc/ → '/contafc'
// Si corre en http://localhost:8080/ → ''
define('BASE_URL', '/contafc');

// ─── Base de Datos ────────────────────────────────────────────────────────
// Cambia 'mysql' por 'localhost' o el nombre del contenedor MySQL si difiere
define('DB_HOST',     getenv('DB_HOST')     ?: 'contafc_db');
define('DB_PORT',     (int)(getenv('DB_PORT')  ?: 3306));
define('DB_NAME',     getenv('DB_NAME')     ?: 'contafc');
define('DB_USER',     getenv('DB_USER')     ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'root');

// ─── Aplicación ───────────────────────────────────────────────────────────
define('APP_ENV',      getenv('APP_ENV')      ?: 'development');
define('APP_TIMEZONE', getenv('APP_TIMEZONE') ?: 'America/Tegucigalpa');
define('APP_SECRET',   getenv('APP_SECRET')   ?: 'contafc_secret_2026_change_me');
