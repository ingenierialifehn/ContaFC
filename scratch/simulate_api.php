<?php
$_GET['tipo'] = 'INVENTARIOS';
$_GET['pid'] = '2025';
$_GET['subtipo'] = 'auxiliar';

session_start();
$_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'rol' => 'admin'];
$_SESSION['empresa'] = 1;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../api/libros_oficiales.php';
