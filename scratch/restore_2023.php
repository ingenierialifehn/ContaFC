<?php
// Script de restauración de integridad 2023 (Corregido para evitar duplicados)
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();

echo "Iniciando restauración de datos 2023...\n";

// 1. Asegurar periodos 2023
for ($m = 1; $m <= 12; $m++) {
    $stmt = $db->prepare("INSERT IGNORE INTO periodos (empresa_id, anio, mes, estado) VALUES (1, 2023, ?, 'abierto')");
    $stmt->execute([$m]);
}
echo "Periodos 2023 asegurados.\n";

// 2. Cargar JSON
$jsonPath = __DIR__ . '/../database/excel_2023_cxc.json';
$jsonData = json_decode(file_get_contents($jsonPath), true);

// 3. Asegurar Comprobante 9999 y LIMPIARLO para evitar errores de duplicado
$db->exec("INSERT IGNORE INTO tipos_comprobante (id, empresa_id, codigo, nombre, activo) VALUES (99, 1, 'MIG', 'Migración', 1)");
$db->exec("INSERT IGNORE INTO comprobantes (id, empresa_id, tipo_comp_id, numero, fecha, periodo_id, usuario_id, estado) VALUES (9999, 1, 99, 1, '2023-01-01', 1, 1, 'registrado')");

// LIMPIEZA: Borramos asientos previos del 9999 para poder re-insertar desde la línea 1
$db->exec("DELETE FROM asientos WHERE comprobante_id = 9999");
echo "Limpieza de registros previos completada.\n";

$db->exec("SET foreign_key_checks = 0");
$linea = 1;
$inserted = 0;
$accountsCache = [];

foreach ($jsonData as $row) {
    // Saltamos CxC si el usuario lo pide
    if ($row['acct'] == '11050101' || $row['acct'] == '110301')
        continue;

    $codigo = $row['acct'];
    if (!isset($accountsCache[$codigo])) {
        $db->prepare("INSERT IGNORE INTO puc_cuentas (empresa_id, codigo, nombre, nivel, naturaleza, tipo_cuenta, acepta_movimiento) VALUES (1, ?, ?, 4, 'D', 'A', 1)")
            ->execute([$codigo, $row['desc'] ?? "Cuenta Restaurada $codigo"]);

        $c = $db->prepare("SELECT id FROM puc_cuentas WHERE codigo = ? AND empresa_id = 1");
        $c->execute([$codigo]);
        $accountsCache[$codigo] = $c->fetchColumn();
    }

    $cuentaId = $accountsCache[$codigo];

    $stmt = $db->prepare("INSERT INTO asientos (empresa_id, comprobante_id, linea, cuenta_id, fecha, debito, credito, descripcion, conteo) VALUES (1, 9999, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $linea++,
        $cuentaId,
        $row['fecha'],
        $row['debito'],
        $row['credito'],
        $row['desc'],
        $row['conteo'] ?? null
    ]);
    $inserted++;
}

$db->exec("SET foreign_key_checks = 1");

echo "Proceso completado:\n";
echo "- $inserted registros restaurados.\n";
