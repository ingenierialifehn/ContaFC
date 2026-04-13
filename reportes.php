<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requirePermiso('reportes');
$user    = Auth::user();
$empresa = null;
try {
    $empresa = Database::getInstance()->getPdo()
        ->query("SELECT * FROM empresas WHERE id = " . Auth::empresaId())->fetch();
} catch (\Throwable) {}

$tipoReporte = $_GET['tipo'] ?? 'balance_comprobacion';
$titulosReporte = [
    'balance_comprobacion' => 'Balance de Comprobación',
    'balance_general'      => 'Balance General',
    'estado_resultados'    => 'Estado de Resultados (PYG)',
    'auxiliar'             => 'Auxiliar de Cuenta',
    'isv_report'           => 'Reporte Fiscal de ISV (15%/18%)',
];
$titulo = $titulosReporte[$tipoReporte] ?? 'Reporte';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ContaFC – <?= $titulo ?> | <?= htmlspecialchars($empresa['nombre'] ?? '') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','system-ui','sans-serif']}}}}</script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .sidebar-link{display:flex;align-items:center;gap:.75rem;padding:.625rem 1rem;border-radius:.5rem;color:#94a3b8;font-size:.875rem;font-weight:500;transition:all .15s;}
        .sidebar-link:hover{background:rgba(255,255,255,.08);color:#fff;}
        .sidebar-link.active{background:#2563eb;color:#fff;box-shadow:0 4px 14px rgba(37,99,235,.35);}
        .rep-row-clase{background:#1e3a5f;color:#fff;font-weight:700;font-size:.8rem;}
        .rep-row-grupo{background:#e8f0fe;color:#1e3a5f;font-weight:600;font-size:.8rem;}
        .rep-row-cuenta{background:#fff;color:#334155;font-size:.78rem;}
        .rep-row-subcuenta{background:#f8fafc;color:#475569;font-size:.76rem;padding-left:2rem!important;}
        
        @media print { 
            aside, header, #filter-bar, #btn-print { display: none !important; } 
            body, html { height: auto !important; overflow: visible !important; background: #fff !important; } 
            main { height: auto !important; overflow: visible !important; padding: 0 !important; width: 100% !important; display: block !important; position: static !important; }
            #reporte-container { overflow: visible !important; height: auto !important; border: none !important; box-shadow: none !important; width: 100% !important; margin: 0 !important; }
            .flex-1 { flex: none !important; }
        }
    </style>
</head>
<body class="h-full font-sans flex text-slate-800">
<?php
$mapNav = ['balance_comprobacion'=>'bc','balance_general'=>'bg','estado_resultados'=>'pyg','auxiliar'=>'auxiliar'];
$activeNav = $mapNav[$tipoReporte] ?? '';
require __DIR__ . '/partials/sidebar.php';
?>

<main class="flex-1 overflow-auto flex flex-col">
    <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between sticky top-0 z-10">
        <div>
            <h1 class="text-lg font-bold text-slate-800"><?= htmlspecialchars($titulo) ?></h1>
            <p class="text-xs text-slate-500"><?= htmlspecialchars($empresa['nombre'] ?? '') ?></p>
        </div>
        <button id="btn-print" onclick="window.print()"
                class="px-4 py-2 bg-slate-700 text-white text-sm font-semibold rounded-lg hover:bg-slate-900 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Imprimir
        </button>
    </header>

    <!-- Filtros -->
    <div id="filter-bar" class="bg-white border-b border-slate-100 px-6 py-3 flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-xs text-slate-500 mb-1 font-medium">Desde</label>
            <input id="f-desde" type="date" value="<?= date('Y-01-01') ?>"
                   class="h-8 px-2 border border-slate-300 rounded text-sm focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1 font-medium">Hasta / Corte</label>
            <input id="f-hasta" type="date" value="<?= date('Y-m-d') ?>"
                   class="h-8 px-2 border border-slate-300 rounded text-sm focus:ring-2 focus:ring-blue-500">
        </div>
        <?php if ($tipoReporte === 'auxiliar'): ?>
        <div>
            <label class="block text-xs text-slate-500 mb-1 font-medium">Código de Cuenta</label>
            <input id="f-cuenta" type="text" placeholder="Ej: 110101"
                   class="h-8 px-2 border border-slate-300 rounded text-sm focus:ring-2 focus:ring-blue-500 w-36">
        </div>
        <?php endif; ?>
        <div class="flex items-center gap-2">
            <label class="text-xs text-slate-500 flex items-center gap-1.5 cursor-pointer">
                <input id="f-solo-mov" type="checkbox" class="rounded">
                Solo cuentas con movimiento
            </label>
        </div>
        <button onclick="generarReporte()"
                class="h-8 px-4 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
            Generar
        </button>
    </div>

    <!-- Tabla de reporte -->
    <div class="flex-1 px-4 py-4 overflow-auto">
        <div id="reporte-container" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden print:overflow-visible print:border-none print:shadow-none">
            <div class="px-6 py-16 text-center text-slate-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <p class="font-medium">Seleccione el rango de fechas y presione <b>Generar</b></p>
            </div>
        </div>
    </div>
</main>

<script>
const TIPO_REP = '<?= $tipoReporte ?>';
const EMPRESA_NOMBRE = '<?= addslashes($empresa['nombre'] ?? '') ?>';
const EMPRESA_LOGO = '<?= !empty($empresa['logo_path']) ? BASE_URL . '/' . $empresa['logo_path'] : '' ?>';

async function generarReporte() {
    const desde = document.getElementById('f-desde').value;
    const hasta = document.getElementById('f-hasta').value;
    const solo  = document.getElementById('f-solo-mov').checked ? 1 : 0;
    const cuenta= document.getElementById('f-cuenta')?.value ?? '';

    let url = `<?= BASE_URL ?>/api/reportes.php?tipo=${TIPO_REP}&desde=${desde}&hasta=${hasta}&solo_mov=${solo}&corte=${hasta}&cuenta=${cuenta}`;

    const cont = document.getElementById('reporte-container');
    cont.innerHTML = '<div class="px-6 py-16 text-center text-slate-400 italic">Generando reporte, espere por favor...</div>';

    try {
        const res  = await fetch(url);
        const json = await res.json();
        if (!res.ok) { cont.innerHTML = `<div class="px-6 py-10 text-center text-red-500 font-bold">${json.error||'Error'}</div>`; return; }

        const data = json.data || [];
        if (TIPO_REP === 'balance_comprobacion') renderBalanceComprobacion(data, cont, desde, hasta);
        else if (TIPO_REP === 'balance_general') renderBalanceGeneral(data, cont, hasta);
        else if (TIPO_REP === 'estado_resultados') renderEstadoResultados(data, cont, desde, hasta);
        else if (TIPO_REP === 'auxiliar') renderAuxiliar(data, cont, cuenta, desde, hasta);
        else if (TIPO_REP === 'isv_report') renderISVReport(data, cont, desde, hasta);
    } catch (err) {
        cont.innerHTML = `<div class="px-6 py-10 text-center text-red-500 font-bold">Error de comunicación.</div>`;
    }
}

function fmt(v) {
    return new Intl.NumberFormat('es-HN', {style:'currency',currency:'HNL',minimumFractionDigits:2}).format(v||0);
}

function renderBalanceComprobacion(data, cont, desde, hasta) {
    let totDeb=0, totCre=0;
    const rows = data.map(r => {
        totDeb += parseFloat(r.total_debito);
        totCre += parseFloat(r.total_credito);
        const nivel = parseInt(r.nivel);
        const cls = nivel <= 1 ? 'rep-row-clase' : nivel === 2 ? 'rep-row-grupo' : nivel === 3 ? 'rep-row-cuenta' : 'rep-row-subcuenta';
        const pad = nivel > 1 ? `style="padding-left:${(nivel-1)*16}px"` : '';
        return `<tr class="${cls}">
            <td class="px-3 py-1.5 font-mono" ${pad}>${r.codigo}</td>
            <td class="px-3 py-1.5">${r.nombre}</td>
            <td class="px-3 py-1.5 text-right font-mono">${fmt(r.total_debito)}</td>
            <td class="px-3 py-1.5 text-right font-mono">${fmt(r.total_credito)}</td>
            <td class="px-3 py-1.5 text-right font-mono ${parseFloat(r.saldo)<0?'text-red-600':''}">${fmt(r.saldo)}</td>
        </tr>`;
    }).join('');

    cont.innerHTML = `
        <div class="p-5 border-b border-slate-100 text-center flex flex-col items-center gap-2">
            ${EMPRESA_LOGO ? `<img src="${EMPRESA_LOGO}" class="max-h-16 mb-2">` : ''}
            <div class="text-lg font-bold text-slate-800 uppercase tracking-tight">${EMPRESA_NOMBRE}</div>
            <div class="text-md font-bold text-slate-700">Balance de Comprobación</div>
            <div class="text-sm text-slate-500 italic">Del ${desde} al ${hasta}</div>
        </div>
        <div class="overflow-x-auto print:overflow-visible">
        <table class="w-full text-xs">
            <thead><tr class="bg-slate-800 text-white text-xs uppercase">
                <th class="px-3 py-2 text-left">Código</th>
                <th class="px-3 py-2 text-left">Nombre Cuenta</th>
                <th class="px-3 py-2 text-right">Débitos</th>
                <th class="px-3 py-2 text-right">Créditos</th>
                <th class="px-3 py-2 text-right">Saldo</th>
            </tr></thead>
            <tbody>${rows}</tbody>
            <tfoot class="bg-slate-800 text-white font-bold text-sm">
                <tr>
                    <td colspan="2" class="px-3 py-2 text-right">TOTALES</td>
                    <td class="px-3 py-2 text-right font-mono">${fmt(totDeb)}</td>
                    <td class="px-3 py-2 text-right font-mono">${fmt(totCre)}</td>
                    <td class="px-3 py-2 text-right font-mono ${Math.abs(totDeb-totCre)>0.01?'text-red-400':''}">${fmt(totDeb-totCre)}</td>
                </tr>
            </tfoot>
        </table>
        </div>
        <div class="p-3 text-xs text-slate-400 text-right italic">Generado: ${new Date().toLocaleString('es-HN')} · ${data.length} cuentas</div>`;
}

function renderBalanceGeneral(data, cont, corte) {
    const activos   = data.filter(r => r.tipo_cuenta === 'A');
    const pasivos   = data.filter(r => r.tipo_cuenta === 'P');
    const patrimonio= data.filter(r => r.tipo_cuenta === 'R');

    const sumActivos   = activos.reduce((s,r) => s+parseFloat(r.saldo_neto), 0);
    const sumPasivos   = pasivos.reduce((s,r) => s+parseFloat(r.saldo_neto), 0);
    const sumPatrimonio= patrimonio.reduce((s,r) => s+parseFloat(r.saldo_neto), 0);

    const seccion = (titulo, rows, total) => `
        <div class="mb-4">
            <div class="text-sm font-bold uppercase text-slate-600 border-b border-slate-200 pb-1 mb-1 font-bold">${titulo}</div>
            ${rows.map(r => `
            <div class="flex justify-between py-1 border-b border-slate-50 ${r.nivel<3?'font-bold':(r.nivel===3?'pl-4':'pl-8 text-slate-500')}">
                <span class="font-mono text-[10px] text-slate-400 w-20 flex-shrink-0">${r.codigo}</span>
                <span class="flex-1 px-2">${r.nombre}</span>
                <span class="font-mono text-right w-32">${fmt(r.saldo_neto)}</span>
            </div>`).join('')}
            <div class="flex justify-between py-2 font-bold border-t border-slate-200 mt-1 bg-slate-50 px-1 rounded">
                <span>Total ${titulo}</span>
                <span class="font-mono">${fmt(total)}</span>
            </div>
        </div>`;

    cont.innerHTML = `
        <div class="p-5 border-b border-slate-100 text-center flex flex-col items-center gap-2">
            ${EMPRESA_LOGO ? `<img src="${EMPRESA_LOGO}" class="max-h-16 mb-2">` : ''}
            <div class="text-lg font-bold text-slate-800 uppercase tracking-tight">${EMPRESA_NOMBRE}</div>
            <div class="text-md font-bold text-slate-700">Balance General</div>
            <div class="text-sm text-slate-500 italic">Al ${corte}</div>
        </div>
        <div class="p-5 grid grid-cols-1 lg:grid-cols-2 gap-8 text-xs">
            <div class="space-y-4">
                ${seccion('ACTIVOS', activos, sumActivos)}
            </div>
            <div class="space-y-4">
                ${seccion('PASIVOS', pasivos, sumPasivos)}
                ${seccion('PATRIMONIO', patrimonio, sumPatrimonio)}
                <div class="flex justify-between py-3 px-2 font-bold border-t-2 border-slate-800 text-sm bg-slate-100 rounded">
                    <span>TOTAL PASIVO + PATRIMONIO</span>
                    <span class="font-mono">${fmt(sumPasivos+sumPatrimonio)}</span>
                </div>
            </div>
        </div>`;
}

function renderEstadoResultados(data, cont, desde, hasta) {
    const ing  = data.filter(r => r.tipo_cuenta === 'R' && parseFloat(r.saldo_neto) < 0);
    const gas  = data.filter(r => r.tipo_cuenta === 'G' || r.tipo_cuenta === 'R' && parseFloat(r.saldo_neto) > 0);
    const totIng = ing.reduce((s,r) => s + Math.abs(parseFloat(r.saldo_neto)), 0);
    const totGas = gas.reduce((s,r) => s + Math.abs(parseFloat(r.saldo_neto)), 0);
    const utilidad = totIng - totGas;

    cont.innerHTML = `
        <div class="p-5 border-b border-slate-100 text-center text-slate-800 flex flex-col items-center gap-2">
            ${EMPRESA_LOGO ? `<img src="${EMPRESA_LOGO}" class="max-h-16 mb-2">` : ''}
            <div class="text-lg font-bold uppercase">${EMPRESA_NOMBRE}</div>
            <div class="text-md font-bold">Estado de Resultados</div>
            <div class="text-sm text-slate-500 italic">${desde} – ${hasta}</div>
        </div>
        <div class="p-5 max-w-2xl mx-auto text-xs space-y-6">
            <div class="bg-white p-3 border rounded-lg">
                <div class="font-bold text-slate-600 uppercase text-sm mb-2 border-b pb-1">INGRESOS</div>
                ${ing.map(r=>`<div class="flex justify-between py-1 pl-4 border-b border-slate-50"><span>${r.nombre}</span><span class="font-mono">${fmt(Math.abs(r.saldo_neto))}</span></div>`).join('')}
                <div class="flex justify-between font-bold border-t pt-2 mt-1"><span>Total Ingresos</span><span class="font-mono text-emerald-700">${fmt(totIng)}</span></div>
            </div>
            <div class="bg-white p-3 border rounded-lg">
                <div class="font-bold text-slate-600 uppercase text-sm mb-2 border-b pb-1">GASTOS Y COSTOS</div>
                ${gas.map(r=>`<div class="flex justify-between py-1 pl-4 border-b border-slate-50"><span>${r.nombre}</span><span class="font-mono">${fmt(Math.abs(r.saldo_neto))}</span></div>`).join('')}
                <div class="flex justify-between font-bold border-t pt-2 mt-1"><span>Total Gastos</span><span class="font-mono text-red-600">${fmt(totGas)}</span></div>
            </div>
            <div class="flex justify-between font-bold text-lg border-t-2 border-slate-800 pt-3 px-1 text-slate-900">
                <span>${utilidad >= 0 ? 'UTILIDAD DEL PERÍODO' : 'PÉRDIDA DEL PERÍODO'}</span>
                <span class="font-mono ${utilidad>=0?'text-emerald-700':'text-red-600'}">${fmt(Math.abs(utilidad))}</span>
            </div>
        </div>`;
}

function renderAuxiliar(data, cont, cuenta, desde, hasta) {
    if (!data.length) { cont.innerHTML = '<div class="p-10 text-center text-slate-400 italic">No existen registros para esta cuenta.</div>'; return; }
    cont.innerHTML = `
        <div class="p-5 border-b border-slate-100 text-center flex flex-col items-center gap-2">
            ${EMPRESA_LOGO ? `<img src="${EMPRESA_LOGO}" class="max-h-16 mb-2">` : ''}
            <div class="text-lg font-bold uppercase">${EMPRESA_NOMBRE}</div>
            <div class="text-md font-bold uppercase">Auxiliar de Cuenta: ${cuenta}</div>
            <div class="text-sm text-slate-500 italic">${desde} – ${hasta}</div>
        </div>
        <div class="overflow-x-auto print:overflow-visible">
        <table class="w-full text-[10px]">
            <thead><tr class="bg-slate-800 text-white font-bold">
                <th class="px-3 py-2 text-left">Fecha</th>
                <th class="px-3 py-2 text-left">Tipo</th>
                <th class="px-3 py-2 text-left">Nº</th>
                <th class="px-3 py-2 text-left">Tercero</th>
                <th class="px-3 py-2 text-left">Descripción</th>
                <th class="px-3 py-2 text-right">Débito</th>
                <th class="px-3 py-2 text-right">Crédito</th>
                <th class="px-3 py-2 text-right">Saldo</th>
            </tr></thead>
            <tbody>${data.map(r=>`<tr class="border-b border-slate-100 hover:bg-slate-50 font-medium">
                <td class="px-3 py-1.5 font-mono">${r.fecha}</td>
                <td class="px-3 py-1.5 font-bold text-blue-600 uppercase">${r.tipo_comp}</td>
                <td class="px-3 py-1.5 font-mono text-slate-500">${r.numero}</td>
                <td class="px-3 py-1.5 max-w-[120px] truncate" title="${r.tercero||''}">${r.tercero||'—'}</td>
                <td class="px-3 py-1.5 max-w-[150px] truncate text-slate-500" title="${r.descripcion||''}">${r.descripcion||''}</td>
                <td class="px-3 py-1.5 text-right font-mono text-emerald-700">${parseFloat(r.debito)>0?fmt(r.debito):''}</td>
                <td class="px-3 py-1.5 text-right font-mono text-blue-700">${parseFloat(r.credito)>0?fmt(r.credito):''}</td>
                <td class="px-3 py-1.5 text-right font-mono font-bold ${parseFloat(r.saldo_acumulado)<0?'text-red-600':'text-slate-800'}">${fmt(r.saldo_acumulado)}</td>
            </tr>`).join('')}</tbody>
        </table></div>`;
}

function renderISVReport(data, cont, desde, hasta) {
    if (!data.length) { cont.innerHTML = '<div class="p-10 text-center text-slate-400 italic font-bold">Sin transacciones fiscales.</div>'; return; }
    let totalImp = 0;
    const items = data.map(r => {
        const imp = parseFloat(r.debito > 0 ? r.debito : r.credito);
        totalImp += imp;
        const tasa = r.descripcion.includes('18%') ? 0.18 : 0.15;
        const base = imp / tasa;
        return `<tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
            <td class="px-4 py-3 font-mono text-[10px] text-slate-500">${r.fecha}</td>
            <td class="px-4 py-3">
                <div class="font-black text-slate-800 tracking-tight">${r.tipo}-${r.numero}</div>
                <div class="text-[10px] text-slate-400 uppercase font-bold tracking-tight">${r.rtn||'SIN RTN'}</div>
            </td>
            <td class="px-4 py-3 text-slate-600 font-medium truncate max-w-[200px]">${r.tercero||'Público en General'}</td>
            <td class="px-4 py-3 text-right font-mono font-bold text-slate-400">${fmt(base)}</td>
            <td class="px-4 py-3 text-right font-mono font-black text-blue-700">${fmt(imp)}</td>
            <td class="px-4 py-3 text-center">
                <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded text-[10px] font-black border border-blue-100">${(tasa*100)}%</span>
            </td>
        </tr>`;
    }).join('');

    cont.innerHTML = `
        <div class="p-8 border-b border-slate-100 bg-slate-50/50 text-center flex flex-col items-center gap-2">
            ${EMPRESA_LOGO ? `<img src="${EMPRESA_LOGO}" class="max-h-16 mb-2">` : ''}
            <div class="text-lg font-bold">${EMPRESA_NOMBRE}</div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tighter">Liquidación Informativa de ISV</h2>
            <p class="text-sm text-slate-500 font-medium mt-1 uppercase tracking-widest">${desde} a ${hasta}</p>
        </div>
        <div class="overflow-x-auto p-4 print:p-0">
        <table class="w-full text-xs bg-white rounded-lg overflow-hidden shadow-sm border border-slate-100">
            <thead>
                <tr class="bg-blue-700 text-white uppercase tracking-widest text-[10px] font-black">
                    <th class="px-4 py-4 text-left">Emisión</th>
                    <th class="px-4 py-4 text-left">Documento / Beneficiario</th>
                    <th class="px-4 py-4 text-left">Tercero</th>
                    <th class="px-4 py-4 text-right">Base Est.</th>
                    <th class="px-4 py-4 text-right">Impuesto</th>
                    <th class="px-4 py-4 text-center">Tasa</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 italic">
                ${items}
            </tbody>
            <tfoot class="bg-slate-900 text-white font-black text-sm border-t-2 border-slate-800">
                <tr>
                    <td colspan="4" class="px-6 py-4 text-right text-[10px] uppercase tracking-widest opacity-60">Total Liquidado</td>
                    <td class="px-6 py-4 text-right font-mono text-lg">${fmt(totalImp)}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        </div>`;
}
</script>
</body>
</html>
