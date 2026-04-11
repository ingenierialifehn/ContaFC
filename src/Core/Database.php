<?php
declare(strict_types=1);

namespace ContaFC\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Database – Singleton PDO usando constantes de configuración central.
 */
final class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $host = defined('DB_HOST') ? DB_HOST : 'mysql';
        $port = defined('DB_PORT') ? DB_PORT : 3306;
        $dbName = defined('DB_NAME') ? DB_NAME : 'contafc';
        $user = defined('DB_USER') ? DB_USER : 'root';
        $password = defined('DB_PASSWORD') ? DB_PASSWORD : '';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";

        try {
            $this->pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Error de conexión a la base de datos. Verifique config/app.php',
                (int) $e->getCode(),
                $e
            );
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    private function __clone()
    {
    }
}
