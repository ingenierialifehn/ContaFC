<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;
use ContaFC\Services\OfficialBookService;
Auth::requireAuth();

// Prevenir caché del navegador en reportes generados
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Helpers para reportes
function renderNoData(int $year) {
    echo "<div style='padding:40px; text-align:center; font-family:sans-serif; background:#f8fafc; height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center;'>";
    echo "<div style='background:white; padding:40px; border-radius:24px; box-shadow:0 10px 15px -3px rgba(0,0,0,0.1); max-width:400px;'>";
    echo "<h2 style='color:#e11d48; font-size:24px; font-weight:900; margin-bottom:10px;'>Sin Datos para $year</h2>";
    echo "<p style='color:#64748b; font-size:14px; line-height:1.6;'>No se encontraron registros contables para el periodo seleccionado. Por favor verifique que existan comprobantes en estado 'registrado'.</p>";
    echo "<button onclick='window.close()' style='margin-top:24px; width:100%; padding:12px; background:#0f172a; color:white; border:none; border-radius:12px; font-weight:800; cursor:pointer;'>Cerrar Ventana</button>";
    echo "</div>";
    echo "</div>";
    exit;
}

function fetchGruposNivel2($db, int $eid) {
    $stmt = $db->prepare("SELECT codigo, nombre FROM puc_cuentas WHERE empresa_id = :eid AND LENGTH(codigo) = 2 AND activa = 1 ORDER BY codigo ASC");
    $stmt->execute([':eid' => $eid]);
    $grupos = [];
    foreach ($stmt->fetchAll() as $gn) {
        $grupos[(string)$gn['codigo']] = strtoupper($gn['nombre']);
    }
    return $grupos;
}

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
$projectLogo = "";

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
    
    if (empty($data)) {
        renderNoData($year);
    }

    $gruposNivel2DB = fetchGruposNivel2($db, $eid);
    if ($proyecto_id) {
        $stmtP = Database::getInstance()->getPdo()->prepare("SELECT nombre, logo_path FROM proyectos WHERE id = ?");
        $stmtP->execute([$proyecto_id]);
        $proyRow = $stmtP->fetch();
        $projectName = $proyRow['nombre'] ?? '';
        $projectLogo = $proyRow['logo_path'] ?? null;
    }
} elseif ($tipo === 'RESULTADOS') {
    $proyecto_id = isset($_GET['proyecto_id']) && $_GET['proyecto_id'] !== '' ? (int) $_GET['proyecto_id'] : null;
    $data = $service->getIncomeStatement($eid, $year, $proyecto_id);

    if (empty($data)) {
        renderNoData($year);
    }

    $gruposNivel2DB = fetchGruposNivel2($db, $eid);
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

        <div class="header" style="margin-bottom: 40px; border-bottom: 2px solid #1e293b; padding-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                <!-- Logo Empresa (Izquierda) -->
                <div style="flex: 1; text-align: left;">
                    <?php if (!empty($empresa['logo_path'])): ?>
                        <img src="<?= BASE_URL ?>/<?= $empresa['logo_path'] ?>" style="max-height: 70px; max-width: 180px; object-fit: contain;">
                    <?php endif; ?>
                    <div style="margin-top: 8px;">
                        <p style="font-size: 11px; font-weight: 800; margin: 0; color: #1e293b;"><?= htmlspecialchars($empresa['nombre']) ?></p>
                        <p style="font-size: 9px; color: #64748b; margin: 0;">Nit: <?= htmlspecialchars($empresa['nit'] ?? '—') ?></p>
                    </div>
                </div>

                <!-- Título Central -->
                <div style="flex: 2; text-align: center;">
                    <h1 style="font-size: 26px; margin: 0; color: #1e293b; letter-spacing: -1px;"><?= $tipo === 'RESULTADOS' ? 'ESTADO DE RESULTADOS' : 'BALANCE GENERAL' ?></h1>
                    <p style="font-size: 15px; font-weight: 800; color: #475569; margin: 10px 0; text-transform: uppercase;">
                        <?php if ($tipo === 'RESULTADOS'): ?>
                            Del 1 de Diciembre de <?= $year - 1 ?> al 31 de Diciembre de <?= $year ?>
                        <?php else: ?>
                            Al 31 de Diciembre de <?= $year ?>
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Logo Proyecto (Derecha) -->
                <div style="flex: 1; text-align: right;">
                    <?php if (!empty($projectLogo)): ?>
                        <img src="<?= BASE_URL ?>/<?= $projectLogo ?>" style="max-height: 70px; max-width: 180px; object-fit: contain;">
                    <?php endif; ?>
                    <?php if ($projectName !== ""): ?>
                        <div style="margin-top: 8px;">
                            <p style="font-size: 9px; font-weight: 800; color: #0369a1; margin: 0; text-transform: uppercase;">Proyecto:</p>
                            <p style="font-size: 10px; font-weight: 700; color: #1e293b; margin: 0;"><?= htmlspecialchars($projectName) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($tipo === 'INVENTARIOS' && $subtipo === 'auxiliar'): ?>
            <div style="margin-top: 20px;">

                <table style="width: 100%; border-collapse: collapse; font-family: 'Inter', sans-serif;">
                    <thead>
                        <tr style="border-bottom: 1px solid #000;">
                            <th style="text-align: left; padding: 8px 0; font-size: 12px; width: 12%;">CÓDIGO</th>
                            <th style="text-align: left; padding: 8px 0; font-size: 12px; width: 38%;">NOMBRE DE LA CUENTA</th>
                            <th style="text-align: right; padding: 8px 0; font-size: 12px; width: 15%;">PERIODO ANTERIOR</th>
                            <th style="text-align: right; padding: 8px 0; font-size: 12px; width: 15%;">HASTA HOY</th>
                            <th style="text-align: right; padding: 8px 0; font-size: 12px; width: 12%;">DIFERENCIA</th>
                            <th style="text-align: right; padding: 8px 0; font-size: 12px; width: 8%;">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // ── Pre-paso: mapa de nombres para prefijos de 2 dígitos
                        // Prioridad: consulta directa al PUC > cuentas en $data
                        $grupoNombres = $gruposNivel2DB ?? [];
                        foreach ($data as $r2) {
                            if (strlen((string)$r2['codigo']) === 2 && !isset($grupoNombres[(string)$r2['codigo']])) {
                                $grupoNombres[(string)$r2['codigo']] = strtoupper($r2['nombre']);
                            }
                        }

                        // ── Filtrar: sólo auxiliares (len>=6) con saldo distinto de cero
                        $detalle = array_filter($data, static function($r2) {
                            if (isset($r2['hidden']) && $r2['hidden'] === true) return false;
                            $len = strlen((string)$r2['codigo']);
                            if ($len < 6) return false;
                            return abs((float)$r2['saldo_actual']) > 0.001
                                || abs((float)$r2['saldo_anterior']) > 0.001;
                        });

                        // ── Totalizadores globales
                        $sumasPrev = ['A' => 0, 'P' => 0, 'R' => 0];
                        $sumas     = ['A' => 0, 'P' => 0, 'R' => 0];
                        $sumasDiff = ['A' => 0, 'P' => 0, 'R' => 0];
                        $sections  = [
                            'A' => 'ACTIVOS', 
                            'P' => 'PASIVOS', 
                            'R' => 'PATRIMONIO Y CAPITAL',
                            'O' => 'RESULTADOS (OTROS)'
                        ];
                        $lastType    = null;
                        $currPrefix2 = null;
                        $currGrupoNombre = '';
                        $grupoSaldos = ['prev' => 0, 'actual' => 0, 'diff' => 0];

                        // Closure para emitir fila TOTAL GRUPO
                        $emitTotalGrupo = function() use (&$currGrupoNombre, &$grupoSaldos): void {
                            echo '<tr>';
                            echo '<td colspan="2" style="padding:4px 0 4px 20px;font-weight:800;font-size:11px;">TOTAL '
                                 . htmlspecialchars($currGrupoNombre) . '</td>';
                            echo '<td style="text-align:right;border-top:1px solid #94a3b8;font-weight:800;font-size:11px;">'
                                 . number_format($grupoSaldos['prev'], 2) . '</td>';
                            echo '<td style="text-align:right;border-top:1px solid #94a3b8;font-weight:800;font-size:11px;">'
                                 . number_format($grupoSaldos['actual'], 2) . '</td>';
                            echo '<td style="text-align:right;border-top:1px solid #94a3b8;font-weight:800;font-size:11px;">'
                                 . number_format($grupoSaldos['diff'], 2) . '</td>';
                            echo '<td></td></tr>';
                            echo '<tr style="height:10px;"></tr>';
                        };

                        foreach ($detalle as $r):
                            $tipoCuenta = $r['tipo_cuenta'];
                            $codigo     = (string)$r['codigo'];
                            $saldoPrev  = (float)$r['saldo_anterior'];
                            $saldo      = (float)$r['saldo_actual'];
                            $diff       = (float)$r['diferencia'];
                            $prefix2    = substr($codigo, 0, 2);

                            // Acumular en totales globales
                            // Para evitar doble conteo, solo sumamos las cuentas "hoja" (las que no tienen hijos en el set de datos)
                            $isLeaf = true;
                            foreach ($detalle as $child) {
                                if ($child['codigo'] !== $r['codigo'] && str_starts_with($child['codigo'], $r['codigo'])) {
                                    $isLeaf = false;
                                    break;
                                }
                            }

                            if ($isLeaf) {
                                $sumasPrev[$tipoCuenta] += $saldoPrev;
                                $sumas[$tipoCuenta]     += $saldo;
                            }
                            $sumasDiff[$tipoCuenta] += $diff;

                            // ── Cambio de sección (Activos → Pasivos → Patrimonio)
                            if ($lastType !== $tipoCuenta) {
                                if ($currPrefix2 !== null) {
                                    $emitTotalGrupo();
                                    $currPrefix2 = null;
                                }
                                $lastType = $tipoCuenta;
                                ?>
                                <tr style="background:#f8fafc;">
                                    <td style="font-weight:950;font-size:15px;padding:25px 0 10px 0;"><?= substr($codigo, 0, 1) ?></td>
                                    <td style="font-weight:950;font-size:15px;padding:25px 0 10px 0;"><?= $sections[$tipoCuenta] ?></td>
                                    <td colspan="4"></td>
                                </tr>
                                <?php
                            }

                            // ── Cambio de grupo (prefijo de 2 dígitos)
                            if ($currPrefix2 !== $prefix2) {
                                if ($currPrefix2 !== null) {
                                    $emitTotalGrupo();
                                }
                                $currPrefix2 = $prefix2;
                                $currGrupoNombre = $grupoNombres[$prefix2] ?? $prefix2;
                                $grupoSaldos = ['prev' => 0, 'actual' => 0, 'diff' => 0];
                                ?>
                                <tr>
                                    <td style="font-weight:800;font-size:11px;padding:8px 0 4px 15px;color:#1e293b;"><?= htmlspecialchars($prefix2) ?></td>
                                    <td style="font-weight:800;font-size:11px;padding:8px 0 4px 15px;color:#1e293b;"><?= htmlspecialchars($currGrupoNombre) ?></td>
                                    <td colspan="4"></td>
                                </tr>
                                <?php
                            }

                            // Acumular en grupo
                            $grupoSaldos['prev']   += $saldoPrev;
                            $grupoSaldos['actual'] += $saldo;
                            $grupoSaldos['diff']   += $diff;
                            ?>
                            <tr style="font-size:10px;color:#334155;">
                                <td style="padding:2px 0 2px 30px;"><?= htmlspecialchars($codigo) ?></td>
                                <td><?= htmlspecialchars($r['nombre']) ?></td>
                                <td style="text-align:right;"><?= number_format($saldoPrev, 2) ?></td>
                                <td style="text-align:right;"><?= number_format($saldo, 2) ?></td>
                                <td style="text-align:right;"><?= number_format($diff, 2) ?></td>
                                <td style="text-align:right;font-size:9px;"><?= number_format((float)$r['porcentaje'], 1) ?>%</td>
                            </tr>
                        <?php
                        endforeach;

                        // Cerrar el último grupo pendiente
                        if ($currPrefix2 !== null) {
                            $emitTotalGrupo();
                        }
                        ?>


                        <tr style="height: 40px;"></tr>
                        <tr style="font-weight: 900; font-size: 14px; border-top: 2px solid #000;">
                            <td colspan="2" style="padding: 10px 0;">TOTAL ACTIVOS</td>
                            <td style="text-align: right; border-bottom: 2px solid #000;"><?= number_format($sumasPrev['A'], 2) ?></td>
                            <td style="text-align: right; border-bottom: 2px solid #000;"><?= number_format($sumas['A'], 2) ?></td>
                            <td style="text-align: right; border-bottom: 2px solid #000;"><?= number_format($sumasDiff['A'], 2) ?></td>
                            <td></td>
                        </tr>

                        <tr style="height: 20px;"></tr>
                        <tr style="font-weight: 900; font-size: 13px;">
                            <td colspan="2">TOTAL PASIVOS</td>
                            <td style="text-align: right; border-top: 1px solid #000;"><?= number_format($sumasPrev['P'], 2) ?></td>
                            <td style="text-align: right; border-top: 1px solid #000;"><?= number_format($sumas['P'], 2) ?></td>
                            <td style="text-align: right; border-top: 1px solid #000;"><?= number_format($sumasDiff['P'], 2) ?></td>
                            <td></td>
                        </tr>

                        <tr style="font-weight: 900; font-size: 13px;">
                            <td colspan="2">TOTAL PATRIMONIO Y CAPITAL</td>
                            <td style="text-align: right; border-top: 1px solid #000;"><?= number_format($sumasPrev['R'], 2) ?></td>
                            <td style="text-align: right; border-top: 1px solid #000;"><?= number_format($sumas['R'], 2) ?></td>
                            <td style="text-align: right; border-top: 1px solid #000;"><?= number_format($sumasDiff['R'], 2) ?></td>
                            <td></td>
                        </tr>

                        <tr style="height: 30px;"></tr>
                        <tr style="height: 30px;"></tr>
                        
                        <tr style="height: 30px;"></tr>
                        <tr style="font-weight: 950; font-size: 14px; color: #2563eb;">
                            <td colspan="2" style="padding: 12px 0; text-decoration: underline; border-top: 3px double #000;">TOTAL PASIVO Y PATRIMONIO</td>
                            <td style="text-align: right; border-top: 3px double #000;"><?= number_format($sumasPrev['P'] + $sumasPrev['R'], 2) ?></td>
                            <td style="text-align: right; border-top: 3px double #000;"><?= number_format($sumas['P'] + $sumas['R'], 2) ?></td>
                            <td style="text-align: right; border-top: 3px double #000;"><?= number_format($sumasDiff['P'] + $sumasDiff['R'], 2) ?></td>
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
            <?php
            // Aplicar el mismo filtrado que en el vertical para consistencia
            $detalle = array_filter($data, static function($r2) {
                if (isset($r2['hidden']) && $r2['hidden'] === true) return false;
                $len = strlen((string)$r2['codigo']);
                if ($len < 6) return false; // Solo auxiliares
                return abs((float)$r2['saldo_actual']) > 0.001
                    || abs((float)$r2['saldo_anterior']) > 0.001;
            });

            $activos = [];
            $pasivosPatrimonio = [];
            $sumAct = 0;
            $sumPasPat = 0;

            foreach ($detalle as $r) {
                // Verificar si es hoja para los totales
                $isLeaf = true;
                foreach ($detalle as $child) {
                    if ($child['codigo'] !== $r['codigo'] && str_starts_with($child['codigo'], $r['codigo'])) {
                        $isLeaf = false;
                        break;
                    }
                }

                if ($r['tipo_cuenta'] === 'A') {
                    $activos[] = $r;
                    if ($isLeaf) $sumAct += (float)$r['saldo_actual'];
                } else {
                    $pasivosPatrimonio[] = $r;
                    if ($isLeaf) $sumPasPat += (float)$r['saldo_actual'];
                }
            }

            // Mapa de grupos para subtítulos
            $grupoNombres = $gruposNivel2DB ?? [];
            ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 20px;">
                <!-- COLUMNA ACTIVOS -->
                <div style="border-right: 1px solid #e2e8f0; padding-right: 15px;">
                    <div style="background: #1e293b; color: white; padding: 10px; font-weight: 900; text-transform: uppercase; border-radius: 8px; margin-bottom: 15px; font-size: 12px; display: flex; justify-content: space-between;">
                        <span>ACTIVOS</span>
                        <span>SALDO</span>
                    </div>
                    <?php 
                    $currP2 = null;
                    foreach ($activos as $r): 
                        $p2 = substr((string)$r['codigo'], 0, 2);
                        if ($currP2 !== $p2):
                            $currP2 = $p2;
                            $gNom = $grupoNombres[$p2] ?? $p2;
                    ?>
                        <div style="font-weight: 800; font-size: 10px; background: #f8fafc; padding: 6px 10px; margin-top: 8px; color: #475569; border-left: 3px solid #cbd5e1;">
                            <?= htmlspecialchars($p2) ?> – <?= htmlspecialchars($gNom) ?>
                        </div>
                    <?php endif; ?>
                        <div style="display: flex; justify-content: space-between; padding: 4px 10px 4px 20px; border-bottom: 1px solid #f1f5f9; font-size: 10px;">
                            <span style="color: #64748b; font-size: 9px; margin-right: 10px;"><?= htmlspecialchars($r['codigo']) ?></span>
                            <span style="flex: 1;"><?= htmlspecialchars($r['nombre']) ?></span>
                            <span class="num"><?= number_format((float)$r['saldo_actual'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>

                    <div style="margin-top: 20px; padding: 12px; border-top: 2px solid #1e293b; background: #f8fafc; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 900; font-size: 13px; color: #1e293b;">TOTAL ACTIVOS</span>
                        <span style="font-size: 14px; font-weight: 900; color: #1e293b; border-bottom: 3px double #1e293b;"><?= number_format($sumAct, 2) ?></span>
                    </div>
                </div>

                <!-- COLUMNA PASIVOS Y PATRIMONIO -->
                <div>
                    <div style="background: #1e293b; color: white; padding: 10px; font-weight: 900; text-transform: uppercase; border-radius: 8px; margin-bottom: 15px; font-size: 12px; display: flex; justify-content: space-between;">
                        <span>PASIVOS Y PATRIMONIO</span>
                        <span>SALDO</span>
                    </div>
                    <?php 
                    $currP2 = null;
                    $lastType = null;
                    foreach ($pasivosPatrimonio as $r): 
                        $p2 = substr((string)$r['codigo'], 0, 2);
                        if ($lastType !== $r['tipo_cuenta']):
                            $lastType = $r['tipo_cuenta'];
                            $typeName = ($lastType === 'P') ? 'PASIVOS' : 'PATRIMONIO Y CAPITAL';
                        ?>
                            <div style="font-weight: 900; font-size: 11px; color: #0f172a; margin-top: 15px; margin-bottom: 5px; text-decoration: underline;">
                                <?= $typeName ?>
                            </div>
                        <?php endif;

                        if ($currP2 !== $p2):
                            $currP2 = $p2;
                            $gNom = $grupoNombres[$p2] ?? $p2;
                    ?>
                        <div style="font-weight: 800; font-size: 10px; background: #f8fafc; padding: 6px 10px; margin-top: 8px; color: #475569; border-left: 3px solid #cbd5e1;">
                            <?= htmlspecialchars($p2) ?> – <?= htmlspecialchars($gNom) ?>
                        </div>
                    <?php endif; ?>
                        <div style="display: flex; justify-content: space-between; padding: 4px 10px 4px 20px; border-bottom: 1px solid #f1f5f9; font-size: 10px;">
                            <span style="color: #64748b; font-size: 9px; margin-right: 10px;"><?= htmlspecialchars($r['codigo']) ?></span>
                            <span style="flex: 1;"><?= htmlspecialchars($r['nombre']) ?></span>
                            <span class="num"><?= number_format((float)$r['saldo_actual'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>

                    <div style="margin-top: 20px; padding: 12px; border-top: 2px solid #1e293b; background: #f8fafc; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 900; font-size: 13px; color: #1e293b;">TOTAL PASIVO Y PATRIMONIO</span>
                        <span style="font-size: 14px; font-weight: 900; color: #1e293b; border-bottom: 3px double #1e293b;"><?= number_format($sumPasPat, 2) ?></span>
                    </div>
                </div>
            </div>

            <!-- Sección de Firmas (Igual que en Vertical) -->
            <div style="margin-top: 80px; width: 100%;">
                <div style="display: flex; justify-content: space-around; margin-bottom: 60px;">
                    <div style="text-align: left; width: 200px; border-top: 1px solid #94a3b8; padding-top: 10px;">
                        <p style="font-weight: 800; font-size: 11px; margin: 0;">Contador</p>
                        <p style="font-size: 9px; color: #475569; margin: 0;">TP / C.C.</p>
                    </div>
                    <div style="text-align: left; width: 200px; border-top: 1px solid #94a3b8; padding-top: 10px;">
                        <p style="font-weight: 800; font-size: 11px; margin: 0;">Revisor / Auditor</p>
                        <p style="font-size: 9px; color: #475569; margin: 0;">TP / C.C.</p>
                    </div>
                    <div style="text-align: left; width: 200px; border-top: 1px solid #94a3b8; padding-top: 10px;">
                        <p style="font-weight: 800; font-size: 11px; margin: 0;">Gerente General</p>
                        <p style="font-size: 9px; color: #475569; margin: 0;">C.C.</p>
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
        <?php elseif ($tipo === 'RESULTADOS'): ?>
            <style>
                .res-table { width: 100%; border-collapse: collapse; font-family: 'Inter', sans-serif; font-size: 11px; margin-top: 20px; }
                .res-table th { border-bottom: 2px solid #000; padding: 8px; text-align: left; text-transform: uppercase; }
                .res-table td { padding: 4px 8px; vertical-align: bottom; border-bottom: 1px solid #f1f5f9; }
                .res-header { font-weight: 900; font-size: 13px; color: #000; padding-top: 15px !important; }
                .res-sub { font-weight: 700; color: #1e293b; padding-left: 20px !important; }
                .res-acc { color: #475569; padding-left: 40px !important; }
                .num-col { text-align: right; width: 100px; font-family: 'Courier New', monospace; }
                .negative { color: #dc2626; font-weight: bold; }
                .total-row { font-weight: 900; border-top: 2px solid #000; border-bottom: 3px double #000; background: #f8fafc; }
                .col-1 { width: 40%; }
                .col-2 { width: 15%; }
                .col-3 { width: 15%; }
                .col-4 { width: 15%; }
            </style>

            <table class="res-table">
                <tbody>
                    <?php
                    $totalVentas = 0;
                    $totalCostos = 0;
                    $totalOtrosIng = 0;
                    
                    // Separar cuentas por bloques funcionales (Grupo completo para no perder datos)
                    $ctaVentas = array_filter($data, fn($r) => str_starts_with($r['codigo'], '41'));
                    $ctaCostos = array_filter($data, fn($r) => str_starts_with($r['codigo'], '6') || str_starts_with($r['codigo'], '7'));
                    $ctaOtrosIng = array_filter($data, fn($r) => str_starts_with($r['codigo'], '4') && !str_starts_with($r['codigo'], '41'));
                    
                    $ctaGastosVenta = array_filter($data, fn($r) => str_starts_with($r['codigo'], '51'));
                    $ctaGastosAdmin = array_filter($data, fn($r) => str_starts_with($r['codigo'], '52') || str_starts_with($r['codigo'], '53'));
                    $ctaGastosFinan = array_filter($data, fn($r) => str_starts_with($r['codigo'], '54') || (str_starts_with($r['codigo'], '5') && !in_array(substr($r['codigo'],0,2), ['51','52','53','54'])));
                    
                    $getLeafs = function($set) {
                        $leafs = [];
                        foreach($set as $r) {
                            $isLeaf = true;
                            foreach($set as $child) {
                                if ($child['codigo'] !== $r['codigo'] && str_starts_with((string)$child['codigo'], (string)$r['codigo'])) {
                                    $isLeaf = false; break;
                                }
                            }
                            if ($isLeaf) $leafs[] = $r;
                        }
                        return $leafs;
                    };

                    $leafsVentas = $getLeafs($ctaVentas);
                    $leafsCostos = $getLeafs($ctaCostos);
                    $leafsOtros = $getLeafs($ctaOtrosIng);
                    
                    foreach($leafsVentas as $l) $totalVentas += $l['saldo'];
                    foreach($leafsCostos as $l) $totalCostos += $l['saldo'];
                    foreach($leafsOtros as $l) $totalOtrosIng += $l['saldo'];

                    $ingresosNetos = $totalVentas - $totalCostos + $totalOtrosIng;
                    ?>

                    <tr class="res-header">
                        <td class="col-1">INGRESOS</td>
                        <td class="col-2"></td>
                        <td class="col-3"></td>
                        <td class="col-4 num-col" style="border-bottom: 2px solid #000;"><?= number_format($ingresosNetos, 2) ?></td>
                    </tr>
                    
                    <?php foreach($leafsVentas as $l): ?>
                    <tr>
                        <td class="res-acc"><?= $l['codigo'] ?> <?= $l['nombre'] ?></td>
                        <td class="num-col"></td>
                        <td class="num-col"><?= number_format($l['saldo'], 2) ?></td>
                        <td></td>
                    </tr>
                    <?php endforeach; ?>

                    <?php foreach($leafsCostos as $l): ?>
                    <tr>
                        <td class="res-acc"><?= $l['codigo'] ?> <?= $l['nombre'] ?></td>
                        <td class="num-col"></td>
                        <td class="num-col negative"><?= number_format($l['saldo'] * -1, 2) ?></td>
                        <td></td>
                    </tr>
                    <?php endforeach; ?>

                    <?php foreach($leafsOtros as $l): ?>
                    <tr>
                        <td class="res-acc"><?= $l['codigo'] ?> <?= $l['nombre'] ?></td>
                        <td class="num-col"></td>
                        <td class="num-col"><?= number_format($l['saldo'], 2) ?></td>
                        <td></td>
                    </tr>
                    <?php endforeach; ?>

                    <!-- --- SECCION GASTOS --- -->
                    <?php
                    $leafsGV = $getLeafs($ctaGastosVenta);
                    $leafsGA = $getLeafs($ctaGastosAdmin);
                    $leafsGF = $getLeafs($ctaGastosFinan);
                    
                    $totalGV = 0; foreach($leafsGV as $l) $totalGV += $l['saldo'];
                    $totalGA = 0; foreach($leafsGA as $l) $totalGA += $l['saldo'];
                    $totalGF = 0; foreach($leafsGF as $l) $totalGF += $l['saldo'];
                    $totalGastos = $totalGV + $totalGA + $totalGF;
                    ?>
                    
                    <tr class="res-header">
                        <td>GASTOS</td>
                        <td></td>
                        <td></td>
                        <td class="num-col" style="border-bottom: 2px solid #000;"><?= number_format($totalGastos, 2) ?></td>
                    </tr>

                    <!-- Gastos de Venta -->
                    <tr class="res-sub">
                        <td>Gastos de venta</td>
                        <td></td>
                        <td class="num-col" style="border-top: 1px solid #000;"><?= number_format($totalGV, 2) ?></td>
                        <td></td>
                    </tr>
                    <?php foreach($leafsGV as $l): ?>
                    <tr>
                        <td class="res-acc"><?= $l['codigo'] ?> <?= $l['nombre'] ?></td>
                        <td class="num-col"><?= number_format($l['saldo'], 2) ?></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <?php endforeach; ?>

                    <!-- Gastos Admin -->
                    <tr class="res-sub" style="padding-top: 10px;">
                        <td>Gastos Administrativo</td>
                        <td></td>
                        <td class="num-col" style="border-top: 1px solid #000;"><?= number_format($totalGA, 2) ?></td>
                        <td></td>
                    </tr>
                    <?php foreach($leafsGA as $l): ?>
                    <tr>
                        <td class="res-acc"><?= $l['codigo'] ?> <?= $l['nombre'] ?></td>
                        <td class="num-col"><?= number_format($l['saldo'], 2) ?></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <?php endforeach; ?>

                    <!-- Gastos Financieros -->
                    <tr class="res-sub" style="padding-top: 10px;">
                        <td>Gastos Financieros</td>
                        <td></td>
                        <td class="num-col" style="border-top: 1px solid #000;"><?= number_format($totalGF, 2) ?></td>
                        <td></td>
                    </tr>
                    <?php foreach($leafsGF as $l): ?>
                    <tr>
                        <td class="res-acc"><?= $l['codigo'] ?> <?= $l['nombre'] ?></td>
                        <td class="num-col"><?= number_format($l['saldo'], 2) ?></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <?php endforeach; ?>

                    <tr style="height: 20px;"></tr>

                    <!-- TOTALES FINALES -->
                    <?php 
                        $utilidadAntes = $ingresosNetos - $totalGastos;
                        // Buscar cuenta de impuesto si existe en el data (ej. 2601...)
                        $ctaImp = array_filter($data, fn($r) => str_starts_with($r['codigo'], '26'));
                        $taxValue = 0;
                        foreach($getLeafs($ctaImp) as $l) $taxValue += $l['saldo'];
                        
                        $utilidadNeta = $utilidadAntes - $taxValue;
                    ?>
                    <tr class="res-header" style="border-top: 2px solid #000;">
                        <td>Utilidad antes del Impuesto</td>
                        <td></td>
                        <td></td>
                        <td class="num-col"><?= number_format($utilidadAntes, 2) ?></td>
                    </tr>
                    <?php foreach($getLeafs($ctaImp) as $l): ?>
                    <tr>
                        <td><?= $l['codigo'] ?> <?= $l['nombre'] ?></td>
                        <td></td>
                        <td></td>
                        <td class="num-col"><?= number_format($l['saldo'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <tr class="total-row">
                        <td style="font-size: 14px; padding: 10px;">UTILIDAD NETA</td>
                        <td></td>
                        <td></td>
                        <td class="num-col" style="font-size: 14px;"><?= number_format($utilidadNeta, 2) ?></td>
                    </tr>

                    <!-- Utilidad del periodo (opcional según cuenta 36) -->
                    <?php 
                        $ctaPer = array_filter($data, fn($r) => str_starts_with($r['codigo'], '36'));
                        foreach($getLeafs($ctaPer) as $l):
                    ?>
                    <tr class="res-sub">
                        <td><?= $l['codigo'] ?> <?= $l['nombre'] ?></td>
                        <td></td>
                        <td class="num-col"><?= number_format($l['saldo'], 2) ?></td>
                        <td></td>
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
