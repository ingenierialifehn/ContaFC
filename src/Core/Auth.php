<?php
declare(strict_types=1);

namespace ContaFC\Core;

use RuntimeException;
use PDO;

/**
 * Auth – Sesión simple con permisos multi-empresa.
 */
final class Auth
{
    private static ?array $currentUser = null;

    public static function validate(string $username, string $password): ?array
    {
        $pdo  = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare(
            "SELECT * FROM usuarios WHERE username = :user LIMIT 1"
        );
        $stmt->execute([':user' => $username]);
        $user = $stmt->fetch();

        if (!$user || !$user['activo']) return null;

        if (!password_verify($password, $user['password_hash'])) {
            // Support for dev/seed passwords
            if ($password !== $user['password_hash']) return null;
        }

        // Obtener empresas asociadas
        if ($user['rol'] === 'admin') {
            $stmtE = $pdo->prepare("SELECT id, nombre, logo_path FROM empresas WHERE activa = 1");
            $stmtE->execute();
        } else {
            $stmtE = $pdo->prepare(
                "SELECT e.id, e.nombre, e.logo_path 
                 FROM empresas e 
                 JOIN usuarios_empresas ue ON e.id = ue.empresa_id 
                 WHERE ue.usuario_id = :uid AND e.activa = 1"
            );
            $stmtE->execute([':uid' => $user['id']]);
        }
        $user['empresas'] = $stmtE->fetchAll();

        return $user;
    }

    public static function login(array $user, int $empresaId): bool
    {
        $pdo = Database::getInstance()->getPdo();
        
        if ($user['rol'] !== 'admin') {
            $stmtUE = $pdo->prepare("SELECT 1 FROM usuarios_empresas WHERE usuario_id = :uid AND empresa_id = :eid");
            $stmtUE->execute([':uid' => $user['id'], ':eid' => $empresaId]);
            if (!$stmtUE->fetch()) return false;
        }

        $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id")->execute([':id' => $user['id']]);

        session_regenerate_id(true);
        $_SESSION['user']    = $user;
        $_SESSION['empresa'] = $empresaId;
        self::$currentUser   = $user;
        return true;
    }

    public static function logout(): void { session_destroy(); self::$currentUser = null; }
    public static function check(): bool { return isset($_SESSION['user']); }

    /**
     * Devuelve el usuario de sesión, hidratando 'empresas' desde DB si no existe.
     * Esto garantiza que el sidebar funcione correctamente en cualquier página.
     */
    public static function user(): ?array
    {
        if (!isset($_SESSION['user'])) return null;

        // Si ya tiene empresas cargadas, retornamos directamente
        if (!empty($_SESSION['user']['empresas'])) {
            return $_SESSION['user'];
        }

        // Hidratar empresas desde la base de datos
        try {
            $pdo  = Database::getInstance()->getPdo();
            $user = $_SESSION['user'];

            if ($user['rol'] === 'admin') {
                $stmt = $pdo->prepare("SELECT id, nombre, logo_path FROM empresas WHERE activa = 1 ORDER BY nombre");
                $stmt->execute();
            } else {
                $stmt = $pdo->prepare(
                    "SELECT e.id, e.nombre, e.logo_path
                     FROM empresas e
                     JOIN usuarios_empresas ue ON e.id = ue.empresa_id
                     WHERE ue.usuario_id = :uid AND e.activa = 1
                     ORDER BY e.nombre"
                );
                $stmt->execute([':uid' => $user['id']]);
            }
            $_SESSION['user']['empresas'] = $stmt->fetchAll();
        } catch (\Throwable $e) {
            $_SESSION['user']['empresas'] = [];
        }

        return $_SESSION['user'];
    }

    public static function empresaId(): int { return (int)($_SESSION['empresa'] ?? 0); }
    public static function userId(): int { return (int)(self::user()['id'] ?? 0); }

    public static function requireAuth(): void
    {
        if (!self::check()) { header('Location: ' . BASE_URL . '/login.php'); exit; }
    }

    public static function hasRole(string $role): bool
    {
        $user = self::user();
        return ($user['rol'] ?? '') === $role;
    }

    public static function requireRol(string $role): void
    {
        self::requireAuth();
        if (!self::hasRole($role)) {
            header('Location: ' . BASE_URL . '/dashboard.php?error=rol_denied');
            exit;
        }
    }

    public static function canAccess(string $modulo, string $accion = 'r'): bool
    {
        $user = self::user();
        if (!$user) return false;
        if (($user['rol'] ?? '') === 'admin') return true;
        
        $permsRaw = $user['permisos'] ?? '{}';
        $perms = is_string($permsRaw) ? json_decode($permsRaw, true) : $permsRaw;
        
        $mPerms = $perms[$modulo] ?? null;
        if ($mPerms === null) return false;

        // Si es un array (nueva estructura CRUD)
        if (is_array($mPerms)) {
            return (bool)($mPerms[$accion] ?? false);
        }

        // Retrocompatibilidad: Si es booleano simple, permitimos acceso 'r'
        // Si no es un array, se asume que true significa acceso total or read access.
        return (bool)$mPerms;
    }

    public static function requirePermiso(string $modulo, string $accion = 'r'): void
    {
        self::requireAuth();
        if (!self::canAccess($modulo, $accion)) {
            header('Location: ' . BASE_URL . '/dashboard.php?error=permiso_denied&modulo='.$modulo.'&accion='.$accion);
            exit;
        }
    }
}
