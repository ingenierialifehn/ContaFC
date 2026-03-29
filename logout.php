<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;

Auth::requireAuth();
Auth::logout();
header('Location: ' . BASE_URL . '/login.php');
exit;
