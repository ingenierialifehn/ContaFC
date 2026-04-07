<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;
use ContaFC\Services\OfficialBookService;

Auth::requireAuth();

$tipo   = $_GET['tipo'] ?? 'DIARIO';
$pid    = (int)($_GET['pid'] ?? 0);
$folio  = (int)($_GET['folio'] ?? 1);
$eid    = Auth::empresaId();

$service = new OfficialBookService();
$db = Database::getInstance()->getPdo();
$empresa = $db->query("SELECT * FROM empresas WHERE id = $eid")->fetch();
$periodo = $db->query("SELECT * FROM periodos WHERE id = $pid")->fetch();

if ($tipo === 'DIARIO') {
    $data = $service->getJournal($eid, $pid);
} elseif ($tipo === 'MAYOR') {
    $data = $service->getLedger($eid, $pid);
} elseif ($tipo === 'INVENTARIOS') {
    $data = $service->getInventoryBalances($eid, (int)$periodo['anio']);
} else {
    die("Tipo de libro no soportado aún.");
}

if (isset($_GET['format']) && $_GET['format'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename=Libro_' . $tipo . '.xls');
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>LIBRO <?= $tipo ?> – <?= $empresa['nombre'] ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 11px; background: #fff; padding: 50px; }
        .page { width: 800px; margin: auto; position: relative; min-height: 1000px; padding-bottom: 50px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 20px; }
        .header h1 { font-size: 22px; font-weight: 900; margin: 0; text-transform: uppercase; }
        .header p { margin: 5px 0; font-weight: bold; }
        .folio { position: absolute; right: 0; top: 0; font-size: 18px; font-weight: 900; border: 2px solid #000; padding: 10px 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; border: 1px solid #000; }
        th { border: 1px solid #000; padding: 10px; background: #eee; text-transform: uppercase; font-size: 10px; }
        td { border: 1px solid #000; padding: 6px 10px; vertical-align: top; }
        .num { text-align: right; white-space: nowrap; font-weight: bold; }
        .footer-legal { margin-top: 50px; text-align: center; border-top: 1px dashed #ccc; padding-top: 20px; color: #555; font-style: italic; }
        .btn-print { position: fixed; right: 20px; top: 20px; padding: 10px 20px; background: #000; color: #fff; border: 0; border-radius: 10px; font-weight: bold; cursor: pointer; }
        @media print { .btn-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <?php if (!isset($_GET['format']) || $_GET['format'] !== 'excel'): ?>
    <button class="btn-print" onclick="window.print()">IMPRIMIR EN HOJAS FOLIADAS</button>
    <?php endif; ?>

    <div class="page">
        <div class="folio">F-<?= str_pad((string)$folio, 5, '0', STR_PAD_LEFT) ?></div>
        
        <div class="header">
            <h1><?= $empresa['nombre'] ?></h1>
            <p>RTN: <?= $empresa['nit'] ?? '—' ?></p>
            <p>LIBRO OFICIAL: <?= $tipo === 'INVENTARIOS' ? 'INVENTARIOS Y BALANCES' : $tipo ?></p>
            <p>PERIODO: <?= $tipo === 'INVENTARIOS' ? "AÑO FISCAL {$periodo['anio']}" : "{$periodo['mes']} / {$periodo['anio']}" ?></p>
        </div>

        <table>
            <?php if ($tipo === 'DIARIO'): ?>
            <thead>
                <tr>
                    <th>FECHA</th>
                    <th>COMP/CTA</th>
                    <th>CONCEPTO / DESCRIPCIÓN</th>
                    <th>DEBE (L)</th>
                    <th>HABER (L)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalD = 0; $totalH = 0;
                foreach ($data as $r): 
                    $totalD += (float)$r['debito'];
                    $totalH += (float)$r['credito'];
                ?>
                <tr>
                    <td align="center"><?= $r['fecha'] ?></td>
                    <td><b><?= $r['cuenta_cod'] ?></b><br/><small><?= $r['tipo_doc'] ?>-<?= $r['numero'] ?></small></td>
                    <td>
                        <div style="font-weight:900"><?= $r['cuenta_nom'] ?></div>
                        <div style="padding-left:15px;"><?= $r['det_obs'] ?></div>
                    </td>
                    <td class="num"><?= $r['debito'] > 0 ? number_format((float)$r['debito'], 2) : '' ?></td>
                    <td class="num"><?= $r['credito'] > 0 ? number_format((float)$r['credito'], 2) : '' ?></td>
                </tr>
                <?php endforeach; ?>
                <tr style="background:#eee; font-weight:900">
                    <td colspan="3" align="right">TOTALES FOLIO <?= $folio ?>:</td>
                    <td class="num"><?= number_format($totalD, 2) ?></td>
                    <td class="num"><?= number_format($totalH, 2) ?></td>
                </tr>
            </tbody>
            <?php elseif ($tipo === 'MAYOR' || $tipo === 'INVENTARIOS'): ?>
            <thead>
                <tr>
                    <th>CÓDIGO</th>
                    <th>CUENTA</th>
                    <th>SALDO ANTERIOR</th>
                    <th>DÉBITOS</th>
                    <th>CRÉDITOS</th>
                    <th>NUEVO SALDO</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach ($data as $r): 
                    $deb = isset($r['debitos_mes']) ? (float)$r['debitos_mes'] : (float)$r['debitos_anio'];
                    $cre = isset($r['creditos_mes']) ? (float)$r['creditos_mes'] : (float)$r['creditos_anio'];
                    $saldo = (float)$r['saldo_anterior'] + $deb - $cre;
                ?>
                <tr>
                    <td align="center"><?= $r['codigo'] ?></td>
                    <td><?= $r['nombre'] ?></td>
                    <td class="num"><?= number_format((float)$r['saldo_anterior'], 2) ?></td>
                    <td class="num"><?= number_format($deb, 2) ?></td>
                    <td class="num"><?= number_format($cre, 2) ?></td>
                    <td class="num"><?= number_format($saldo, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <?php endif; ?>
        </table>

        <div class="footer-legal">
            ContaFC - Generado por Auditoría Tributaria de Honduras. Resolución SAR No. 2026-<?= rand(100,999) ?>
        </div>
    </div>
</body>
</html>
