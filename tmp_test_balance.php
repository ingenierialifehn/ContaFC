<?php
require 'bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();

// Todas las empresas
$emps = $db->query("SELECT id, nombre FROM empresas")->fetchAll();
echo "Empresas en DB:\n";
foreach ($emps as $e) echo "  [{$e['id']}] {$e['nombre']}\n";

// Todos los proyectos con su empresa
$proys = $db->query("SELECT p.id, p.empresa_id, p.nombre, p.codigo, e.nombre as empresa FROM proyectos p JOIN empresas e ON e.id = p.empresa_id ORDER BY p.empresa_id, p.id")->fetchAll();
echo "\nProyectos en DB:\n";
foreach ($proys as $p) echo "  emp=[{$p['empresa_id']}] {$p['empresa']} | proy=[{$p['id']}] cod={$p['codigo']} nombre={$p['nombre']}\n";

// Usuarios y sus empresas
$users = $db->query("SELECT id, username, empresa_id FROM usuarios")->fetchAll();
echo "\nUsuarios:\n";
foreach ($users as $u) echo "  [{$u['id']}] {$u['username']} -> empresa_id={$u['empresa_id']}\n";
