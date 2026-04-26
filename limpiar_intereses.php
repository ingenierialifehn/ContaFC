<?php
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();

// Manejar movimiento a 2024 si se presiona el botón
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mover_id'], $_POST['nueva_fecha'])) {
    $id = (int)$_POST['mover_id'];
    $nueva_fecha = trim($_POST['nueva_fecha']);
    
    try {
        if (empty($nueva_fecha) || !strtotime($nueva_fecha)) {
            throw new Exception("Debes especificar una fecha válida.");
        }
        
        $consecutivo = "MIG-" . $nueva_fecha;

        // 1. Buscar o crear el comprobante de retención para esa fecha
        $stmt_check = $db->prepare("SELECT id FROM comprobantes WHERE observaciones = :consec LIMIT 1");
        $stmt_check->execute(['consec' => $consecutivo]);
        $comprobante_2024_id = $stmt_check->fetchColumn();

        if (!$comprobante_2024_id) {
            // Conseguir ID empresa y tipo_comprobante del comprobante ORIGINAL del asiento
            $stmt_info = $db->prepare("
                SELECT c.empresa_id, c.tipo_comp_id, c.periodo_id 
                FROM comprobantes c
                JOIN asientos a ON a.comprobante_id = c.id
                WHERE a.id = :aid
                LIMIT 1
            ");
            $stmt_info->execute(['aid' => $id]);
            $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

            $empresa_id = $info ? $info['empresa_id'] : 1;
            $tipo_comp_id = $info ? $info['tipo_comp_id'] : 1;
            $periodo_fallback = $info ? $info['periodo_id'] : 1;

            // Conseguir o crear alias al periodo correcto
            $stmtPeriodo = $db->prepare("SELECT id FROM periodos WHERE empresa_id = :eid AND mes = MONTH(:fecha_mes) AND anio = YEAR(:fecha_anio) LIMIT 1");
            $stmtPeriodo->execute(['eid' => $empresa_id, 'fecha_mes' => $nueva_fecha, 'fecha_anio' => $nueva_fecha]);
            $periodo_id = $stmtPeriodo->fetchColumn() ?: $periodo_fallback;

            // Obtener el siguiente número de comprobante
            $numStmt = $db->prepare("SELECT COALESCE(MAX(numero), 0) + 1 FROM comprobantes WHERE empresa_id = :eid AND tipo_comp_id = :tid");
            $numStmt->execute(['eid' => $empresa_id, 'tid' => $tipo_comp_id]);
            $numero = $numStmt->fetchColumn() ?: 99999;

            $stmt_insert = $db->prepare("
                INSERT INTO comprobantes (empresa_id, tipo_comp_id, numero, fecha, observaciones, tercero_id, usuario_id, estado, periodo_id)
                VALUES (:empresa_id, :tipo_comp, :numero, :fecha, :consec, NULL, 1, 'registrado', :periodo)
            ");
            $stmt_insert->execute([
                'empresa_id' => $empresa_id, 
                'tipo_comp' => $tipo_comp_id, 
                'numero' => $numero, 
                'consec' => $consecutivo, 
                'fecha' => $nueva_fecha,
                'periodo' => $periodo_id
            ]);
            $comprobante_2024_id = $db->lastInsertId();
        }

        // 2. Apuntar el asiento al comprobante del 2024
        $stmt_update = $db->prepare("UPDATE asientos SET comprobante_id = :comp_id WHERE id = :id");
        $stmt_update->execute(['comp_id' => $comprobante_2024_id, 'id' => $id]);
        
        $mensaje = "¡Éxito! El Asiento #$id ha sido salvado y movido exitosamente a la fecha $nueva_fecha.";
    } catch (\Throwable $e) {
        $mensaje_error = "Error al mover: " . $e->getMessage();
    }
}

// Consultar todos los sospechosos
$sql = "SELECT 
            a.id AS asiento_id, 
            c.id AS comprobante_id,
            c.fecha, 
            p.codigo AS cuenta_codigo, 
            p.nombre AS cuenta_nombre, 
            a.descripcion, 
            a.debito, 
            a.credito,
            t.razon_social AS tercero_nombre
        FROM asientos a
        JOIN comprobantes c ON a.comprobante_id = c.id
        JOIN puc_cuentas p ON a.cuenta_id = p.id
        LEFT JOIN terceros t ON a.tercero_id = t.id OR c.tercero_id = t.id
        WHERE p.nombre LIKE '%clientes%' 
          AND a.descripcion LIKE '%Intereses%Financia%' 
          AND a.credito < 0
          AND c.estado = 'registrado'
        ORDER BY c.fecha ASC";

$stmt = $db->query($sql);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_creditos = 0;
$suma_por_ano = [];

foreach ($registros as $row) {
    $ano = date('Y', strtotime($row['fecha']));
    if (!isset($suma_por_ano[$ano])) {
        $suma_por_ano[$ano] = 0;
    }
    $suma_por_ano[$ano] += $row['credito'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Auditoría de Intereses Financiación</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f7f6; }
        table { border-collapse: collapse; width: 100%; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background-color: #2c3e50; color: white; }
        .danger { color: red; font-weight: bold; }
        .success { color: green; font-weight: bold; }
        .btn-delete { background-color: #e74c3c; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px; }
        .btn-delete:hover { background-color: #c0392b; }
        .summary { background: #fff; border-left: 4px solid #3498db; padding: 15px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
    </style>
</head>
<body>

    <h1>Auditoría de Asientos Sospechosos (Intereses Financiación)</h1>
    
    <?php if (isset($mensaje)): ?>
        <div style="background-color: #2ecc71; color: white; padding: 10px; margin-bottom: 15px;">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($mensaje_error)): ?>
        <div style="background-color: #e74c3c; color: white; padding: 10px; margin-bottom: 15px;">
            <?= htmlspecialchars($mensaje_error) ?>
        </div>
    <?php endif; ?>

    <div class="summary">
        <h3>Instrucciones Milimétricas:</h3>
        <p>Busca en esta lista aquellos registros que sepas por tu DBF que tienen fechas diferentes (como el 28 de febrero de 2024). En lugar de borrarlos, ahora puedes seleccionar su fecha original. Escribe o selecciona la fecha correcta en la cajita y da clic en <strong>"Reubicar a Fecha"</strong>. Al dar clic, el sistema creará mágicamente un comprobante con esa fecha exacta y recatará tu línea, estabilizando tu balance.</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID Asiento</th>
                <th>ID Comp.</th>
                <th>Tercero</th>
                <th>Fecha Actual</th>
                <th>Descripción</th>
                <th>Débito</th>
                <th>Crédito</th>
                <th style="min-width: 250px;">Asignar Fecha Corecta</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($registros as $row): ?>
                <?php $total_creditos += $row['credito']; ?>
                <tr>
                    <td><?= htmlspecialchars($row['asiento_id']) ?></td>
                    <td><?= htmlspecialchars($row['comprobante_id']) ?></td>
                    <td><?= htmlspecialchars($row['tercero_nombre'] ?? 'Sin Tercero') ?></td>
                    <td><?= htmlspecialchars($row['fecha']) ?></td>
                    <td><?= htmlspecialchars($row['descripcion']) ?></td>
                    <td><?= number_format($row['debito'], 2) ?></td>
                    <td class="danger"><?= number_format($row['credito'], 2) ?></td>
                    <td>
                        <form method="POST" style="display:flex; gap:5px;" onsubmit="return confirm('¿Aseguras que deseas cambiar la fecha de este asiento (ID: <?= $row['asiento_id'] ?>)? Desaparecerá de su fecha actual y se moverá.');">
                            <input type="hidden" name="mover_id" value="<?= $row['asiento_id'] ?>">
                            <input type="date" name="nueva_fecha" required style="padding: 5px;">
                            <button type="submit" class="btn-delete" style="background-color: #3498db; white-space: nowrap;">Reubicar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            
            <?php if (empty($registros)): ?>
                <tr><td colspan="9">No hay registros problemáticos. Todo limpio.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 30px; padding: 20px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.2);">
        <h3>Resumen Matemático (Suma de los créditos defectuosos por año)</h3>
        <ul style="font-size: 1.2em;">
            <?php foreach ($suma_por_ano as $ano => $suma): ?>
                <li>Año <strong><?= $ano ?></strong>: <span class="danger"><?= number_format($suma, 2) ?></span></li>
            <?php endforeach; ?>
        </ul>
        <hr>
        <h3 style="text-align: right;">
            Suma Total Histórica Anómala: <span class="danger"><?= number_format($total_creditos, 2) ?></span>
        </h3>
    </div>

</body>
</html>
