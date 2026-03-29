<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Services\FixedAssetService;

Auth::requireAuth();

$eid = Auth::empresaId();
$service = new FixedAssetService();
$activos = $service->getAll($eid);

// Headers for Excel
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename=Inventario_Activos_Fijos_' . date('Ymd') . '.xls');

?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<table border="1">
    <thead>
        <tr style="background-color: #1e3a5f; color: white; font-weight: bold;">
            <th>Código</th>
            <th>Nombre</th>
            <th>CECO</th>
            <th>Fecha Compra</th>
            <th>Costo Adquisición (L.)</th>
            <th>Valor Salvamento (L.)</th>
            <th>Vida Útil (Meses)</th>
            <th>Depreciación Mensual (L.)</th>
            <th>Depreciación Acumulada (L.)</th>
            <th>Valor Neto en Libros (L.)</th>
            <th>Estado</th>
            <th>Cuenta Activo</th>
            <th>Cuenta Gasto</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($activos as $a): 
            $costo = (float)$a['costo_adquisicion'];
            $depAcum = (float)$a['depreciacion_acumulada'];
            $neto = $costo - $depAcum;
        ?>
        <tr>
            <td><?= $a['codigo'] ?></td>
            <td><?= $a['nombre'] ?></td>
            <td><?= $a['ceco_nom'] ?></td>
            <td><?= $a['fecha_compra'] ?></td>
            <td align="right"><?= number_format($costo, 2, '.', '') ?></td>
            <td align="right"><?= number_format((float)$a['valor_salvamento'], 2, '.', '') ?></td>
            <td align="center"><?= $a['vida_util_meses'] ?></td>
            <td align="right"><?= number_format((float)$a['depreciacion_mensual'], 2, '.', '') ?></td>
            <td align="right"><?= number_format($depAcum, 2, '.', '') ?></td>
            <td align="right"><?= number_format($neto, 2, '.', '') ?></td>
            <td><?= strtoupper($a['estado']) ?></td>
            <td><?= $a['cta_activo_cod'] ?> - <?= $a['cta_activo_nom'] ?></td>
            <td><?= $a['cta_gas_cod'] ?> - <?= $a['cta_gas_nom'] ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
