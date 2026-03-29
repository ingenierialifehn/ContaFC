<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/bootstrap.php';

try {
    $db = ContaFC\Core\Database::getInstance()->getPdo();
    $hash = password_hash('Admin2026!', PASSWORD_BCRYPT);
    $stmt = $db->prepare("UPDATE usuarios SET password_hash = :hash WHERE username IN ('admin', 'contador3')");
    $stmt->execute([':hash' => $hash]);
    
    echo "<h1>¡CORRECCIÓN APLICADA CON ÉXITO! ✅</h1>";
    echo "<p>Se actualizaron " . $stmt->rowCount() . " usuarios en tu base local.</p>";
    echo "<p>Las cuentas <strong>admin</strong> y <strong>contador3</strong> ahora usan la encriptación BCrypt nativa compatible con tu Windows PHP.</p>";
    echo "<br><a href='login.php' style='padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 5px; font-family: sans-serif'>Ir a Iniciar Sesión</a>";
} catch (\Exception $e) {
    echo "<h1>Error</h1><p>" . $e->getMessage() . "</p>";
}
