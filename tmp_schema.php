<?php
require 'bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

$stmt = $db->query("SHOW TABLES LIKE '%proyectos%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $db->query("SHOW CREATE TABLE comprobantes");
print_r($stmt->fetch(PDO::FETCH_ASSOC));

$stmt = $db->query("SHOW CREATE TABLE asientos");
print_r($stmt->fetch(PDO::FETCH_ASSOC));

$stmt = $db->query("SHOW CREATE TABLE usuarios");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
