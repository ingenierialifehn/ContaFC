<?php
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Auth;
use ContaFC\Core\Database;

// Simular login de usuario 1 empresa 1
$_SESSION['user_id'] = 1;
$_SESSION['empresa_id'] = 1;

$_GET['desde'] = '2023-01-01';
$_GET['hasta'] = '2026-12-31';
$_GET['estado'] = 'registrado';

ob_start();
include __DIR__ . '/api/comprobantes.php';
$output = ob_get_clean();

echo "API OUTPUT:\n";
echo $output;
