<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT id, nombre, codigo FROM proyectos")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
