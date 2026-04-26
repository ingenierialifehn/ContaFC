<?php
$_GET['tipo'] = 'INVENTARIOS';
$_GET['pid'] = '2024';
$_GET['subtipo'] = 'capital';

session_start();
$_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'rol' => 'admin'];
$_SESSION['empresa'] = 1;

require_once __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../api/libros_oficiales.php';
