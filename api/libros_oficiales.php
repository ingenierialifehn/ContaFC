<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;
use ContaFC\Services\OfficialBookService;

Auth::requireAuth();

$tipo = $_GET['tipo'] ?? 'DIARIO';
$pid = (int) ($_GET['pid'] ?? 0);
$folio = (int) ($_GET['folio'] ?? 1);
$eid = Auth::empresaId();
$subtipo = $_GET['subtipo'] ?? 'auxiliar';

$service = new OfficialBookService();
$db = Database::getInstance()->getPdo();
$empresa = $db->query("SELECT * FROM empresas WHERE id = $eid")->fetch();

// Si es INVENTARIOS (Balance General), el $pid es realmente el AÑO si viene de la nueva selección
$year = $pid > 1000 ? $pid : (int) date('Y');
$data = [];
$projectName = "";

if ($tipo === 'DIARIO') {
    $data = $service->getJournal($eid, $pid);
} elseif ($tipo === 'MAYOR') {
    $data = $service->getLedger($eid, $pid);
} elseif ($tipo === 'INVENTARIOS') {
    $proyecto_id = isset($_GET['proyecto_id']) && $_GET['proyecto_id'] !== '' ? (int) $_GET['proyecto_id'] : null;

    if ($proyecto_id) {
        $stmtProyecto = $db->prepare("SELECT id, codigo, nombre FROM proyectos WHERE id = :id AND empresa_id = :eid LIMIT 1");
        $stmtProyecto->execute([':id' => $proyecto_id, ':eid' => $eid]);
        $proyecto = $stmtProyecto->fetch();

        if ($proyecto) {
            $codigoProyecto = strtolower(trim((string) ($proyecto['codigo'] ?? '')));
            $nombreProyecto = strtolower(trim((string) ($proyecto['nombre'] ?? '')));

            if (in_array($codigoProyecto, ['gen', 'general'], true) || in_array($nombreProyecto, ['general', 'todos', 'todas'], true)) {
                $proyecto_id = null;
            } else {
                $stmtMov = $db->prepare("SELECT 1 FROM asientos WHERE empresa_id = :eid AND proyecto_id = :pid LIMIT 1");
                $stmtMov->execute([':eid' => $eid, ':pid' => $proyecto_id]);
                if (!$stmtMov->fetchColumn()) {
                    $proyecto_id = null;
                }
            }
        } else {
            $proyecto_id = null;
        }
    }

    $data = $service->getComparativeBalance($eid, $year, $proyecto_id);

    if ($proyecto_id) {
        $stmtP = Database::getInstance()->getPdo()->prepare("SELECT nombre, logo_path FROM proyectos WHERE id = ?");
        $stmtP->execute([$proyecto_id]);
        $proyRow = $stmtP->fetch();
        $projectName = $proyRow['nombre'] ?? '';
        $projectLogo = $proyRow['logo_path'] ?? null;
    }
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
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: 10px;
            background: #fff;
            padding: 20px;
            color: #334155;
        }

        .page {
            width: 900px;
            margin: auto;
            position: relative;
            min-height: 1000px;
            padding: 40px;
            border: 1px solid #eee;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 900;
            margin: 0;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: -0.5px;
        }

        .header p {
            margin: 2px 0;
            font-weight: 600;
            color: #64748b;
            font-size: 11px;
        }

        .folio {
            position: absolute;
            right: 40px;
            top: 40px;
            font-size: 16px;
            font-weight: 900;
            color: #e2e8f0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            padding: 10px;
            background: #f8fafc;
            text-transform: uppercase;
            font-size: 9px;
            font-weight: 800;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        td {
            padding: 6px 10px;
            vertical-align: middle;
            border: 1px solid #f1f5f9;
        }

        .num {
            text-align: right;
            white-space: nowrap;
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            font-weight: 600;
        }

        .txt-bold {
            font-weight: 700;
            color: #0f172a;
        }

        .level-1 {
            background: #f1f5f9;
            font-weight: 800;
            font-size: 11px;
        }

        .level-2 {
            background: #f8fafc;
            font-weight: 700;
            padding-left: 20px;
        }

        .level-3 {
            padding-left: 35px;
        }

        .level-4 {
            padding-left: 50px;
            color: #64748b;
        }

        .footer-legal {
            margin-top: 50px;
            text-align: center;
            padding-top: 20px;
            color: #94a3b8;
            font-size: 9px;
            border-top: 1px solid #f1f5f9;
        }

        .btn-print {
            position: fixed;
            right: 20px;
            top: 20px;
            padding: 12px 24px;
            background: #0f172a;
            color: #fff;
            border: 0;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.2s;
        }

        .btn-print:hover {
            transform: translateY(-2px);
            background: #1e293b;
        }

        .grid-balance {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        .section-title {
            background: #1e293b;
            color: white;
            padding: 8px 15px;
            font-weight: 900;
            text-transform: uppercase;
            border-radius: 4px;
            margin-bottom: 10px;
            font-size: 11px;
        }

        .total-row {
            background: #f1f5f9;
            font-weight: 900;
        }

        @media print {
            .btn-print {
                display: none;
            }

            body {
                padding: 0;
            }

            .page {
                border: none;
                box-shadow: none;
                width: 100%;
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <?php if (!isset($_GET['format']) || $_GET['format'] !== 'excel'): ?>
        <button class="btn-print" onclick="window.print()">IMPRIMIR REPORTE PROFESIONAL</button>
    <?php endif; ?>

    <div class="page">
        <div class="folio">Pág. <?= $folio ?></div>

        <div class="header" style="position: relative; margin-bottom: 50px;">
            <div style="position: absolute; left: 0; top: 0; text-align: left;">
                <p>Empresa: <?= $empresa['id'] ?></p>
                <p>Destino: Todos</p>
            </div>

            <div style="position: absolute; right: 0; top: 0; text-align: right;">
                <p>Nit: <?= $empresa['nit'] ?? '—' ?></p>
            </div>

            <div style="display: flex; align-items: center; justify-content: center; gap: 20px; margin-top: 20px;">
                <?php 
                    $activeLogo = ($projectName !== "" && !empty($projectLogo)) ? $projectLogo : ($empresa['logo_path'] ?? null);
                    if ($activeLogo): 
                ?>
                    <img src="<?= BASE_URL ?>/<?= $activeLogo ?>" style="max-height: 80px; max-width: 200px; object-contain: contain;">
                <?php endif; ?>
                <h1 style="margin: 0;"><?= $empresa['nombre'] ?></h1>
            </div>
            <?php if ($projectName !== ""): ?>
                <p style="font-size: 12px; font-weight: 700; margin: 6px 0 0; color: #475569;">
                    Proyecto: <?= htmlspecialchars($projectName) ?>
                </p>
            <?php endif; ?>
            <p style="font-size: 16px; letter-spacing: 2px; font-weight: 800; margin: 10px 0;">BALANCE GENERAL</p>

            <div style="display: flex; justify-content: center; gap: 40px; margin-top: 20px; font-weight: 700;">
                <!-- <div style="text-align: center;">
                    <p style="color: #94a3b8; font-size: 9px; text-transform: uppercase;">Periodo Anterior</p>
                    <p>31/12/<?= $year - 1 ?></p>
                </div> -->
                <div style="text-align: center;">
                    <p style="color: #94a3b8; font-size: 9px; text-transform: uppercase;">Al</p>
                    <p>31/12/<?= $year ?></p>
                </div>
            </div>
        </div>

        <?php if ($tipo === 'INVENTARIOS' && $subtipo === 'auxiliar'): ?>
            <div style="margin-top: 20px;">
                <p style="font-weight: 800; font-size: 16px; margin-bottom: 15px;">Balance General Vertical - <?= $year ?>
                </p>

                <table style="width: 100%; border-collapse: collapse; font-family: 'Inter', sans-serif;">
                    <thead>
                        <tr style="border-bottom: 1px solid #000;">
                            <th style="text-align: left; padding: 8px 0; font-size: 12px; width: 15%;">CÓDIGO</th>
                            <th style="text-align: left; padding: 8px 0; font-size: 12px; width: 45%;">NOMBRE DE LA CUENTA
                            </th>
                            <th style="text-align: right; padding: 8px 0; font-size: 12px; width: 15%;">HASTA HOY</th>
                            <th style="text-align: right; padding: 8px 0; font-size: 12px; width: 15%;">DIFERENCIA</th>
                            <th style="text-align: right; padding: 8px 0; font-size: 12px; width: 10%;">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sumas = ['A' => 0, 'P' => 0, 'R' => 0];
                        $sections = ['A' => 'ACTIVOS', 'P' => 'PASIVOS', 'R' => 'PATRIMONIO Y CAPITAL'];
                        $lastType = null;

                        $currGrupo = null;
                        $currGrupoNombre = "";
                        $grupoSaldos = ['actual' => 0, 'diff' => 0];

                        foreach ($data as $r):
                            $tipoCuenta = $r['tipo_cuenta'];
                            $len = strlen($r['codigo']);
                            $saldo = (float) $r['saldo_actual'];
                            $diff = (float) $r['diferencia'];

                            // Acumular totales generales solo de las cuentas de detalle para evitar duplicidad
                            if ($len >= 6) {
                                $sumas[$tipoCuenta] += $saldo;
                            }

                            // 1. Encabezado de Sección Grande (Activos, Pasivos, etc)
                            if ($lastType !== $tipoCuenta) {
                                if ($currGrupo !== null) {
                                    ?>
                                    <tr>
                                        <td colspan="2" style="padding: 4px 0 4px 20px; font-weight: 800; font-size: 11px;">TOTAL
                                            <?= $currGrupoNombre ?></td>
                                        <td
                                            style="text-align: right; border-top: 1px solid #94a3b8; font-weight: 800; font-size: 11px;">
                                            <?= number_format($grupoSaldos['actual'], 2) ?></td>
                                        <td
                                            style="text-align: right; border-top: 1px solid #94a3b8; font-weight: 800; font-size: 11px;">
                                            <?= number_format($grupoSaldos['diff'], 2) ?></td>
                                        <td></td>
                                    </tr>
                                    <tr style="height: 10px;"></tr>
                                    <?php
                                    $currGrupo = null;
                                }

                                $lastType = $tipoCuenta;
                                ?>
                                <tr style="background: #f8fafc;">
                                    <td style="font-weight: 950; font-size: 15px; padding: 25px 0 10px 0;">
                                        <?= substr($r['codigo'], 0, 1) ?></td>
                                    <td style="font-weight: 950; font-size: 15px; padding: 25px 0 10px 0;">
                                        <?= $sections[$tipoCuenta] ?></td>
                                    <td colspan="3"></td>
                                </tr>
                                <?php
                            }

                            $esGrupo = ($len == 2);
                            $esDetalle = ($len >= 6);

                            if ($esGrupo) {
                                if ($currGrupo !== null && $currGrupo !== $r['codigo']) {
                                    ?>
                                    <tr>
                                        <td colspan="2" style="padding: 4px 0 4px 20px; font-weight: 800; font-size: 11px;">TOTAL
                                            <?= $currGrupoNombre ?></td>
                                        <td
                                            style="text-align: right; border-top: 1px solid #94a3b8; font-weight: 800; font-size: 11px;">
                                            <?= number_format($grupoSaldos['actual'], 2) ?></td>
                                        <td
                                            style="text-align: right; border-top: 1px solid #94a3b8; font-weight: 800; font-size: 11px;">
                                            <?= number_format($grupoSaldos['diff'], 2) ?></td>
                                        <td></td>
                                    </tr>
                                    <tr style="height: 10px;"></tr>
                                    <?php
                                    $grupoSaldos = ['actual' => 0, 'diff' => 0];
                                }
                                $currGrupo = $r['codigo'];
                                $currGrupoNombre = strtoupper($r['nombre']);
                                ?>
                                <tr>
                                    <td style="font-weight: 800; font-size: 11px; padding: 8px 0 4px 15px; color: #1e293b;">
                                        <?= $r['codigo'] ?></td>
                                    <td style="font-weight: 800; font-size: 11px; padding: 8px 0 4px 15px; color: #1e293b;">
                                        <?= strtoupper($r['nombre']) ?></td>
                                    <td colspan="3"></td>
                                </tr>
                                <?php
                            } elseif ($esDetalle && abs($saldo) > 0.001) {
                                $grupoSaldos['actual'] += $saldo;
                                $grupoSaldos['diff'] += $diff;
                                ?>
                                <tr style="font-size: 10px; color: #334155;">
                                    <td style="padding: 2px 0 2px 25px;"><?= $r['codigo'] ?></td>
                                    <td><?= $r['nombre'] ?></td>
                                    <td style="text-align: right;"><?= number_format($saldo, 2) ?></td>
                                    <td style="text-align: right;"><?= number_format($diff, 2) ?></td>
                                    <td style="text-align: right; font-size: 9px;"><?= number_format((float) $r['porcentaje'], 1) ?>%
                                    </td>
                                </tr>
                                <?php
                            }
                        endforeach;

                        if ($currGrupo !== null) { ?>
                            <tr>
                                <td colspan="2" style="padding: 4px 0 4px 20px; font-weight: 800; font-size: 11px;">TOTAL
                                    <?= $currGrupoNombre ?></td>
                                <td
                                    style="text-align: right; border-top: 1px solid #94a3b8; font-weight: 800; font-size: 11px;">
                                    <?= number_format($grupoSaldos['actual'], 2) ?></td>
                                <td
                                    style="text-align: right; border-top: 1px solid #94a3b8; font-weight: 800; font-size: 11px;">
                                    <?= number_format($grupoSaldos['diff'], 2) ?></td>
                                <td></td>
                            </tr>
                        <?php } ?>

                        <tr style="height: 40px;"></tr>
                        <tr style="font-weight: 900; font-size: 14px; border-top: 2px solid #000;">
                            <td colspan="2" style="padding: 10px 0;">TOTAL ACTIVOS</td>
                            <td style="text-align: right; border-bottom: 2px solid #000;">
                                <?= number_format($sumas['A'], 2) ?></td>
                            <td style="text-align: right; border-bottom: 2px solid #000;">
                                <?= number_format($sumas['A'], 2) ?></td>
                            <td></td>
                        </tr>

                        <tr style="height: 20px;"></tr>
                        <tr style="font-weight: 900; font-size: 13px;">
                            <td colspan="2">TOTAL PASIVOS</td>
                            <td style="text-align: right; border-top: 1px solid #000;"><?= number_format($sumas['P'], 2) ?>
                            </td>
                            <td style="text-align: right; border-top: 1px solid #000;"><?= number_format($sumas['P'], 2) ?>
                            </td>
                            <td></td>
                        </tr>

                        <tr style="font-weight: 900; font-size: 13px;">
                            <td colspan="2">TOTAL PATRIMONIO Y CAPITAL</td>
                            <td style="text-align: right; border-top: 1px solid #000;"><?= number_format($sumas['R'], 2) ?>
                            </td>
                            <td style="text-align: right; border-top: 1px solid #000;"><?= number_format($sumas['R'], 2) ?>
                            </td>
                            <td></td>
                        </tr>

                        <tr style="height: 30px;"></tr>
                        <tr style="font-weight: 950; font-size: 14px; color: #2563eb;">
                            <td colspan="2"
                                style="padding: 12px 0; text-decoration: underline; border-top: 3px double #000;">TOTAL
                                PASIVO Y PATRIMONIO</td>
                            <td style="text-align: right; border-top: 3px double #000;">
                                <?= number_format($sumas['P'] + $sumas['R'], 2) ?></td>
                            <td style="text-align: right; border-top: 3px double #000;">
                                <?= number_format($sumas['P'] + $sumas['R'], 2) ?></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>

                <div style="margin-top: 80px; width: 100%;">
                    <div style="display: flex; justify-content: space-around; margin-bottom: 60px;">
                        <div style="text-align: left; width: 250px; border-top: 1px solid #94a3b8; padding-top: 10px;">
                            <p style="font-weight: 800; font-size: 12px;">Contador</p>
                            <p style="font-size: 10px; color: #475569;">TP</p>
                            <p style="font-size: 10px; color: #475569;">C.C.</p>
                        </div>
                        <div style="text-align: left; width: 250px; border-top: 1px solid #94a3b8; padding-top: 10px;">
                            <p style="font-weight: 800; font-size: 12px;">Revisor</p>
                            <p style="font-size: 10px; color: #475569;">TP</p>
                            <p style="font-size: 10px; color: #475569;">C.C.</p>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: center;">
                        <div style="text-align: left; width: 250px; border-top: 1px solid #94a3b8; padding-top: 10px;">
                            <p style="font-weight: 800; font-size: 12px;">Gerente</p>
                            <p style="font-size: 10px; color: #475569;">TP</p>
                            <p style="font-size: 10px; color: #475569;">C.C.</p>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($tipo === 'INVENTARIOS' && $subtipo === 'capital'): ?>
            <div class="grid-balance">
                <div class="col-activos">
                    <div class="section-title">Activos</div>
                    <?php
                    $sumAct = 0;
                    foreach ($data as $r):
                        if ($r['tipo_cuenta'] !== 'A')
                            continue;
                        $nivel = (int) $r['nivel'];
                        if ($nivel == 1)
                            $sumAct = (float) $r['saldo_actual'];

                        $cls = "level-" . $nivel;
                        ?>
                        <div class="<?= $cls ?>"
                            style="display: flex; justify-content: space-between; padding: 4px 8px; border-bottom: 1px solid #f1f5f9; <?= $nivel < 3 ? 'font-weight: 800; background: #f8fafc;' : '' ?>">
                            <span style="flex: 1;"><?= $r['nombre'] ?></span>
                            <span class="num"><?= number_format((float) $r['saldo_actual'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="total-row"
                        style="display: flex; justify-content: space-between; padding: 12px; margin-top: 15px; border-top: 3px double #000; background: #f1f5f9;">
                        <span class="txt-bold" style="flex: 1; font-size: 12px;">TOTAL ACTIVO</span>
                        <span class="num" style="font-size: 13px; font-weight: 900;"><?= number_format($sumAct, 2) ?></span>
                    </div>
                </div>

                <div class="col-pasivos">
                    <div class="section-title">Pasivos y Patrimonio</div>
                    <?php
                    $sumPas = 0;
                    $sumPat = 0;
                    foreach ($data as $r):
                        if ($r['tipo_cuenta'] !== 'P' && $r['tipo_cuenta'] !== 'R')
                            continue;
                        $nivel = (int) $r['nivel'];
                        if ($nivel == 1) {
                            if ($r['tipo_cuenta'] == 'P')
                                $sumPas = (float) $r['saldo_actual'];
                            else
                                $sumPat = (float) $r['saldo_actual'];
                        }

                        $cls = "level-" . $nivel;
                        ?>
                        <div class="<?= $cls ?>"
                            style="display: flex; justify-content: space-between; padding: 4px 8px; border-bottom: 1px solid #f1f5f9; <?= $nivel < 3 ? 'font-weight: 800; background: #f8fafc;' : '' ?>">
                            <span style="flex: 1;"><?= $r['nombre'] ?></span>
                            <span class="num"><?= number_format((float) $r['saldo_actual'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="total-row"
                        style="display: flex; justify-content: space-between; padding: 12px; margin-top: 15px; border-top: 3px double #000; background: #1e293b; color: white;">
                        <span class="txt-bold" style="flex: 1; font-size: 12px;">TOTAL PASIVO MAS PATRIMONIO</span>
                        <span class="num"
                            style="font-size: 13px; font-weight: 900;"><?= number_format($sumPas + $sumPat, 2) ?></span>
                    </div>
                </div>
            </div>

        <?php elseif ($tipo === 'DIARIO'): ?>
            <table>
                <thead>
                    <tr>
                        <th>FECHA</th>
                        <th>CTA/COMP</th>
                        <th>CONCEPTO / DESCRIPCIÓN</th>
                        <th>DEBE (L)</th>
                        <th>HABER (L)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $totalD = 0;
                    $totalH = 0;
                    foreach ($data as $r):
                        $totalD += (float) $r['debito'];
                        $totalH += (float) $r['credito'];
                        ?>
                        <tr>
                            <td align="center" class="txt-bold"><?= $r['fecha'] ?></td>
                            <td><b><?= $r['cuenta_cod'] ?></b><br /><small><?= $r['tipo_doc'] ?>-<?= $r['numero'] ?></small>
                            </td>
                            <td>
                                <div class="txt-bold" style="color: #475569"><?= $r['cuenta_nom'] ?></div>
                                <div style="padding-left:15px; color: #64748b; font-style: italic;"><?= $r['det_obs'] ?></div>
                            </td>
                            <td class="num"><?= $r['debito'] > 0 ? number_format((float) $r['debito'], 2) : '' ?></td>
                            <td class="num"><?= $r['credito'] > 0 ? number_format((float) $r['credito'], 2) : '' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="3" align="right">TOTALES:</td>
                        <td class="num"><?= number_format((float) $totalD, 2) ?></td>
                        <td class="num"><?= number_format((float) $totalH, 2) ?></td>
                    </tr>
                </tbody>
            </table>

        <?php elseif ($tipo === 'MAYOR'): ?>
            <table>
                <thead>
                    <tr>
                        <th>CÓDIGO</th>
                        <th>CUENTA</th>
                        <th class="num">SALDO ANTERIOR</th>
                        <th class="num">DÉBITOS</th>
                        <th class="num">CRÉDITOS</th>
                        <th class="num">NUEVO SALDO</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($data as $r):
                        $deb = (float) $r['debitos_mes'];
                        $cre = (float) $r['creditos_mes'];
                        $saldo = (float) $r['saldo_anterior'] + ($r['naturaleza'] == 'D' ? ($deb - $cre) : ($cre - $deb));
                        ?>
                        <tr>
                            <td align="center" class="txt-bold"><?= $r['codigo'] ?></td>
                            <td><?= $r['nombre'] ?></td>
                            <td class="num"><?= number_format((float) $r['saldo_anterior'], 2) ?></td>
                            <td class="num"><?= number_format((float) $deb, 2) ?></td>
                            <td class="num"><?= number_format((float) $cre, 2) ?></td>
                            <td class="num txt-bold"><?= number_format((float) $saldo, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="footer-legal">
            ContaFC – Sistema de Gestión Contable Profesional. Honduras <?= date('Y') ?>.
            Reproducción autorizada para efectos fiscales y auditoría interna.
        </div>
    </div>
    <script>
        if (window.innerWidth > 1000) {
            document.querySelector('.page').style.transform = 'scale(0.9)';
            document.querySelector('.page').style.transformOrigin = 'top center';
        }
    </script>
</body>

</html>
