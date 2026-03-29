<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empresaId = (int)($_POST['empresa_id'] ?? 0);
    $user = Auth::user();

    if (!$user) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }

    // Validar si el usuario tiene acceso a esa empresa
    $canSwitch = false;
    if ($user['rol'] === 'admin') {
        $canSwitch = true;
    } else {
        foreach ($user['empresas'] as $emp) {
            if ((int)$emp['id'] === $empresaId) {
                $canSwitch = true;
                break;
            }
        }
    }

    if ($canSwitch) {
        $_SESSION['empresa'] = $empresaId;
        // Limpiar el caché de empresas para que se recarguen en el próximo request
        if (isset($_SESSION['user']['empresas'])) {
            unset($_SESSION['user']['empresas']);
        }
        $referer = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/dashboard.php');
        header('Location: ' . $referer);
        exit;
    }
}

header('Location: ' . BASE_URL . '/dashboard.php?error=switch_denied');
exit;
