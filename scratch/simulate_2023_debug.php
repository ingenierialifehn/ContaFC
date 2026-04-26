<?php
$_GET['tipo'] = 'INVENTARIOS';
$_GET['pid'] = '2023';
$_GET['subtipo'] = 'capital';

session_start();
$_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'rol' => 'admin'];
$_SESSION['empresa'] = 1;

require_once __DIR__ . '/../bootstrap.php';
// Enable all errors
ini_set('display_errors', '1');
error_reporting(E_ALL);

require __DIR__ . '/../api/libros_oficiales.php';
