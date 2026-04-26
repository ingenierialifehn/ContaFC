<?php
$_GET['tipo'] = 'INVENTARIOS';
$_GET['pid'] = '2023';
$_GET['subtipo'] = 'capital';

var_dump($_GET);
session_start();
$_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'rol' => 'admin'];
$_SESSION['empresa'] = 1;

ob_start();
require_once __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../api/libros_oficiales.php';
$html = ob_get_clean();

file_put_contents(__DIR__ . '/simulate_out.html', $html);
echo "HTML SAVED TO simulate_out.html\n";
