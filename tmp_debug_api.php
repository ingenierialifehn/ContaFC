<?php
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Database;
use ContaFC\Core\Auth;

// Simular login if needed for API to work in CLI
$_SESSION['user'] = ['id' => 1, 'rol' => 'admin'];
$_SESSION['empresa'] = 1;

$_GET['q'] = 'WINER';
$_GET['tipo'] = 'cliente';

ob_start();
require __DIR__ . '/api/terceros.php';
$output = ob_get_clean();

file_put_contents(__DIR__ . '/tmp_api_debug.json', $output);
echo "Output saved to tmp_api_debug.json\n";
