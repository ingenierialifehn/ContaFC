<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requirePermiso('comprobantes');
$user    = Auth::user();
$empresa = null;
try {
    $db = Database::getInstance()->getPdo();
    $empresa = $db->query("SELECT * FROM empresas WHERE id = " . Auth::empresaId())->fetch();
} catch (\Throwable $e) {}

$activeNav = 'comprobantes';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ContaFC – Comprobantes | <?= htmlspecialchars($empresa['nombre'] ?? '') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT:'#1e3a5f', light:'#2563eb', dark:'#0f1f3d' },
                        accent: '#f59e0b',
                    },
                    fontFamily: { sans: ['Inter','system-ui','sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
</head>
<body class="h-full font-sans flex">

<?php require __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-1 overflow-auto flex flex-col">
    <!-- Topbar -->
    <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between sticky top-0 z-10">
        <div>
            <h1 class="text-lg font-bold text-slate-800">Listado de Comprobantes</h1>
            <p class="text-xs text-slate-500">Histórico de asientos contables · <?= date('Y') ?></p>
        </div>
        <div class="flex items-center gap-3">
             <a href="<?= BASE_URL ?>/asiento.php" 
                class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nuevo Asiento
            </a>
        </div>
    </header>

    <!-- Filtros -->
    <div class="bg-white border-b border-slate-200 px-6 py-4 flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-xs text-slate-500 mb-1 font-medium">Desde</label>
            <input id="f-desde" type="text" value="<?= date('Y-m-01') ?>" placeholder="DD/MM/AAAA"
                   class="h-9 px-3 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all w-32">
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1 font-medium">Hasta</label>
            <input id="f-hasta" type="text" value="<?= date('Y-m-d') ?>" placeholder="DD/MM/AAAA"
                   class="h-9 px-3 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all w-32">
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1 font-medium">Estado</label>
            <select id="f-estado" class="h-9 px-3 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                <option value="registrado">Registrados</option>
                <option value="borrador">Borradores</option>
                <option value="anulado">Anulados</option>
            </select>
        </div>
        <button onclick="cargarListado()" 
                class="h-9 px-6 bg-slate-800 text-white text-sm font-semibold rounded-lg hover:bg-slate-900 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            Filtrar
        </button>
    </div>

    <!-- Tabla -->
    <div class="p-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-100">
                            <th class="px-4 py-3 text-left font-semibold">Referencia</th>
                            <th class="px-4 py-3 text-left font-semibold">Tipo</th>
                            <th class="px-4 py-3 text-left font-semibold">Fecha</th>
                            <th class="px-4 py-3 text-left font-semibold">Tercero Principal</th>
                            <th class="px-4 py-3 text-right font-semibold">Total Débitos</th>
                            <th class="px-4 py-3 text-right font-semibold">Total Créditos</th>
                            <th class="px-4 py-3 text-center font-semibold">Estado</th>
                            <th class="px-4 py-3 text-center font-semibold">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="lista-comprobantes" class="divide-y divide-slate-100 italic text-slate-400">
                        <tr><td colspan="8" class="text-center py-10">Use los filtros para buscar información...</td></tr>
                    </tbody>
                </table>
            </div>
            <!-- Paginación -->
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="text-xs text-slate-500 order-2 sm:order-1" id="info-count">Mostrando 0 registros</div>
                <div class="flex gap-2 order-1 sm:order-2">
                    <button id="btn-prev" 
                            class="px-4 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold text-slate-600 hover:bg-white hover:shadow-sm disabled:opacity-30 disabled:hover:shadow-none transition-all flex items-center gap-1" disabled>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Anterior
                    </button>
                    <button id="btn-next" 
                            class="px-4 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold text-slate-600 hover:bg-white hover:shadow-sm disabled:opacity-30 disabled:hover:shadow-none transition-all flex items-center gap-1" disabled>
                        Siguiente
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
let currentPage = 1;
const recordsLimit = 50;

document.addEventListener('DOMContentLoaded', () => {
    flatpickr("#f-desde, #f-hasta", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d/m/Y",
        allowInput: true,
        locale: "es"
    });
    cargarListado(1);
});

async function cargarListado(page = 1) {
    currentPage = page;
    const desde = document.getElementById('f-desde').value;
    const hasta = document.getElementById('f-hasta').value;
    const estado= document.getElementById('f-estado').value;

    const tbody = document.getElementById('lista-comprobantes');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-10"><div class="flex items-center justify-center gap-3"><div class="w-5 h-5 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div> Cargando datos...</div></td></tr>';

    try {
        const res  = await fetch(`<?= BASE_URL ?>/api/comprobantes.php?desde=${desde}&hasta=${hasta}&estado=${estado}&page=${currentPage}&limit=${recordsLimit}`);
        const json = await res.json();
        const data = json.data || [];
        const pag  = json.pagination || {};

        if (!data.length) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-10 text-slate-500">No se encontraron comprobantes para el filtro aplicado.</td></tr>';
            document.getElementById('info-count').textContent = 'Mostrando 0 de 0 registros';
            updatePaginationControls(0, 0);
            return;
        }

        tbody.innerHTML = data.map(r => {
            const displayFecha = r.fecha_asiento || r.fecha;
            const formatoFecha = displayFecha ? displayFecha.split('-').reverse().join('/') : '';
            return `
            <tr class="hover:bg-slate-50/80 transition-colors group">
                <td class="px-4 py-3 font-mono text-slate-700 font-medium">${r.tipo_comp}-${String(r.numero).padStart(5,'0')}</td>
                <td class="px-4 py-2.5">
                    <div class="text-slate-900 font-semibold">${r.tipo_nombre}</div>
                    <div class="text-[11px] text-slate-400 truncate max-w-[250px]" title="${r.observaciones || ''}">${r.observaciones || '<span class="italic opacity-50">Sin observaciones</span>'}</div>
                </td>
                <td class="px-4 py-3 text-slate-500 whitespace-nowrap text-xs">${formatoFecha}</td>
                <td class="px-4 py-3 text-slate-600 font-medium text-xs truncate max-w-[150px]">${r.tercero || '<span class="text-slate-300">—</span>'}</td>
                <td class="px-4 py-3 text-right font-mono text-emerald-700 font-semibold">${fmtHNL(r.total_debitos)}</td>
                <td class="px-4 py-3 text-right font-mono text-blue-700 font-semibold">${fmtHNL(r.total_creditos)}</td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] uppercase font-bold tracking-wider ${estadoBadge(r.estado)}">${r.estado}</span>
                </td>
                <td class="px-4 py-3 text-center">
                    <a href="<?= BASE_URL ?>/asiento.php?id=${r.id}" 
                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-all shadow-sm border border-transparent hover:border-blue-100" title="Ver / Editar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </a>
                </td>
            </tr>
        `}).join('');

        const start = ((pag.page - 1) * pag.limit) + 1;
        const end   = Math.min(pag.page * pag.limit, pag.total);
        document.getElementById('info-count').innerHTML = `Mostrando <span class="font-bold text-slate-800">${start}-${end}</span> de <span class="font-bold text-slate-800">${pag.total}</span> registros`;
        
        updatePaginationControls(pag.page, pag.pages);

    } catch (e) {
        console.error(e);
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-10 text-red-500 font-medium">Error en el servidor al cargar los datos. Intente más tarde.</td></tr>';
    }
}

function updatePaginationControls(current, total) {
    const btnPrev = document.getElementById('btn-prev');
    const btnNext = document.getElementById('btn-next');
    
    btnPrev.disabled = current <= 1;
    btnNext.disabled = current >= total;
    
    btnPrev.onclick = () => cargarListado(current - 1);
    btnNext.onclick = () => cargarListado(current + 1);
}

function fmtHNL(v) {
    return new Intl.NumberFormat('es-HN', { style:'currency', currency:'HNL', minimumFractionDigits:2 }).format(parseFloat(v)||0);
}

function estadoBadge(estado) {
    const m = { 
        registrado: 'bg-emerald-50 text-emerald-700 border border-emerald-100', 
        borrador:   'bg-amber-50 text-amber-700 border border-amber-100', 
        anulado:    'bg-red-50 text-red-700 border border-red-100' 
    };
    return m[estado] || 'bg-slate-50 text-slate-600 border border-slate-100';
}
</script>
</body>
</html>
