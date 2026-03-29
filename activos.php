<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();

$user    = Auth::user();
$empresa = null;
$periodos = [];
try {
    $db = Database::getInstance()->getPdo();
    $empresa = $db->query("SELECT * FROM empresas WHERE id = " . Auth::empresaId())->fetch();
    $periodos = $db->query("SELECT * FROM periodos WHERE empresa_id = " . Auth::empresaId() . " AND estado = 'abierto' ORDER BY anio DESC, mes DESC")->fetchAll();
} catch (\Throwable $e) {}

$activeNav = 'activos';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50 text-slate-700">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activos Fijos – <?= htmlspecialchars($empresa['nombre'] ?? 'ContaFC') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT:'#1e3a5f', light:'#2563eb', dark:'#0f1f3d' },
                        honduras: '#0073cf',
                        firebird: '#e53e3e',
                    },
                    fontFamily: { sans: ['Inter','system-ui','sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .sidebar-link { display:flex; align-items:center; gap:.75rem; padding:.625rem 1rem; border-radius:.5rem; color:#94a3b8; font-size:.875rem; font-weight:500; transition:all .15s; }
        .sidebar-link:hover { background:rgba(255,255,255,.08); color:#fff; }
        .sidebar-link.active { background:#2563eb; color:#fff; box-shadow:0 4px 14px rgba(37,99,235,.35); }
    </style>
</head>
<body class="h-full font-sans flex text-sm overflow-hidden text-slate-700">

<?php require __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-1 flex flex-col min-w-0 bg-slate-50">
    <!-- Header -->
    <header class="bg-white border-b border-slate-200 px-8 py-5 flex items-center justify-between z-10 shadow-sm">
        <div class="flex items-center gap-6">
            <div class="w-14 h-14 bg-honduras/10 text-honduras rounded-3xl flex items-center justify-center shadow-inner">
                 <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </div>
            <div>
                <nav class="flex text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 gap-2">
                    <span class="text-honduras">Activos y Control</span>
                    <span>/</span>
                    <span>Honduras - NIIF</span>
                </nav>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight leading-none">Gestión de Activos Fijos</h1>
                <p class="text-slate-500 text-xs mt-1">Control patrimonial, amortización y depreciación acumulada.</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?= BASE_URL ?>/api/activos_excel.php" 
               class="bg-emerald-600 px-5 py-3 text-white rounded-2xl hover:bg-emerald-700 transition font-bold shadow-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Exportar Excel
            </a>
            <button onclick="modalProcesarDepreciacion()" 
                    class="bg-slate-800 px-5 py-3 text-white rounded-2xl hover:bg-slate-900 transition font-bold shadow-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Procesar Mes
            </button>
            <button onclick="abrirModalActivo()" 
                    class="bg-honduras px-5 py-3 text-white rounded-2xl hover:opacity-90 transition font-bold shadow-lg shadow-blue-500/20 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Registrar Activo
            </button>
        </div>
    </header>

    <div class="flex-1 overflow-auto p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Widgets de Resumen -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Costo Adquisición Total</p>
                    <h3 class="text-2xl font-black text-slate-800" id="stat-costo">L. 0.00</h3>
                </div>
                <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Depreciación Acumulada</p>
                    <h3 class="text-2xl font-black text-rose-600" id="stat-dep">L. 0.00</h3>
                </div>
                <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Valor Neto en Libros</p>
                    <h3 class="text-2xl font-black text-emerald-600" id="stat-neto">L. 0.00</h3>
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl overflow-hidden">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-slate-400 text-[10px] uppercase font-bold tracking-widest border-b border-slate-100 bg-slate-50/50">
                            <th class="px-8 py-5">Código / Activo</th>
                            <th class="px-8 py-5">Ubicación / CECO</th>
                            <th class="px-8 py-5 text-right">Costo / Salvam.</th>
                            <th class="px-8 py-5">Vida Útil / Progreso</th>
                            <th class="px-8 py-5 text-right">Dep. Acumulada</th>
                            <th class="px-8 py-5 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="activos-body" class="divide-y divide-slate-50">
                        <tr><td colspan="6" class="text-center py-20 text-slate-400 italic">Cargando inventario de activos...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
let activos = [];

document.addEventListener('DOMContentLoaded', cargarActivos);

async function cargarActivos() {
    const res = await fetch('<?= BASE_URL ?>/api/activos.php');
    const json = await res.json();
    activos = json.data || [];
    renderActivos();
    actualizarEstadisticas();
}

function renderActivos() {
    const body = document.getElementById('activos-body');
    if (!activos.length) {
        body.innerHTML = '<tr><td colspan="6" class="text-center py-24 text-slate-400">No hay activos fijos registrados en el sistema.</td></tr>';
        return;
    }

    body.innerHTML = activos.map(a => {
        const porc = Math.min(100, (parseFloat(a.depreciacion_acumulada) / (parseFloat(a.costo_adquisicion) - parseFloat(a.valor_salvamento))) * 100);
        return `
        <tr class="hover:bg-slate-50 transition-all group">
            <td class="px-8 py-5">
                <div class="flex flex-col">
                    <span class="font-mono font-bold text-honduras text-xs tracking-widest">${a.codigo}</span>
                    <span class="font-bold text-slate-800 text-base leading-tight mt-1">${a.nombre}</span>
                    <span class="text-[10px] text-slate-400 mt-1 uppercase font-bold tracking-tighter">${a.fecha_compra}</span>
                </div>
            </td>
            <td class="px-8 py-5">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-tighter">${a.ceco_nom || 'SIN CECO'}</span>
            </td>
            <td class="px-8 py-5 text-right">
                <div class="font-mono font-bold text-slate-700">${fmtHNL(a.costo_adquisicion)}</div>
                <div class="text-[10px] text-slate-400">Salvam: ${fmtHNL(a.valor_salvamento)}</div>
            </td>
            <td class="px-8 py-5">
                <div class="flex flex-col gap-1.5 w-full max-w-[120px]">
                    <div class="flex justify-between text-[10px] font-black tracking-widest uppercase">
                        <span>Depreciación:</span>
                        <span>${Math.round(porc)}%</span>
                    </div>
                    <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-honduras" style="width: ${porc}%"></div>
                    </div>
                    <span class="text-[10px] text-slate-400 font-bold mt-0.5 uppercase">${a.vida_util_meses} Meses</span>
                </div>
            </td>
            <td class="px-8 py-5 text-right font-mono font-black text-rose-600">
                ${fmtHNL(a.depreciacion_acumulada)}
            </td>
            <td class="px-8 py-5 text-center">
                <button onclick="verHistorialDep(${a.id})" class="p-2.5 bg-white border border-slate-200 text-slate-400 hover:text-honduras hover:border-honduras rounded-xl shadow-sm transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </button>
            </td>
        </tr>
    `}).join('');
}

function actualizarEstadisticas() {
    const costo = activos.reduce((s, a) => s + parseFloat(a.costo_adquisicion), 0);
    const dep   = activos.reduce((s, a) => s + parseFloat(a.depreciacion_acumulada), 0);
    document.getElementById('stat-costo').innerText = fmtHNL(costo);
    document.getElementById('stat-dep').innerText   = fmtHNL(dep);
    document.getElementById('stat-neto').innerText  = fmtHNL(costo - dep);
}

function abrirModalActivo() {
    Swal.fire({
        title: 'Registrar Activo Fijo',
        width: '800px',
        html: `
            <div class="grid grid-cols-2 gap-6 text-left p-4">
                <div class="col-span-2 section-title border-b border-slate-100 pb-2 mb-2 text-honduras font-black text-[10px] uppercase tracking-widest">Información Básica</div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Código Interno</label>
                    <input id="sw_codigo" placeholder="Ej: AF-01" class="w-full h-11 border border-slate-200 rounded-xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras text-sm font-mono font-bold">
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Nombre del Activo</label>
                    <input id="sw_nombre" placeholder="Ej: Aire Acondicionado 12000 BTU" class="w-full h-11 border border-slate-200 rounded-xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras text-sm font-bold">
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Fecha Compra</label>
                    <input id="sw_fecha" type="date" value="<?= date('Y-m-d') ?>" class="w-full h-11 border border-slate-200 rounded-xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras text-sm">
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Vida Útil (Meses)</label>
                    <input id="sw_vida" type="number" value="60" class="w-full h-11 border border-slate-200 rounded-xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras text-sm font-bold">
                </div>

                <div class="col-span-2 section-title border-b border-slate-100 pb-2 mb-2 mt-4 text-honduras font-black text-[10px] uppercase tracking-widest">Valores Monetarios</div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Costo Adquisición (L)</label>
                    <input id="sw_costo" type="number" step="0.01" class="w-full h-11 border border-slate-200 rounded-xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras text-sm font-mono font-bold">
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Valor Salvamento (L)</label>
                    <input id="sw_salvamento" type="number" step="0.01" value="0" class="w-full h-11 border border-slate-200 rounded-xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras text-sm font-mono font-bold">
                </div>

                <div class="col-span-2 section-title border-b border-slate-100 pb-2 mb-2 mt-4 text-honduras font-black text-[10px] uppercase tracking-widest">Cuentas Contables y CECO</div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Centro de Costo</label>
                    <select id="sw_ceco" class="w-full h-11 border border-slate-200 rounded-xl px-4 outline-none text-xs font-bold">
                        <option value="">Seleccione CECO...</option>
                        <!-- Se cargará via JS -->
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Cuenta Activo (15)</label>
                    <input id="sw_cta_act" placeholder="Buscar código..." class="w-full h-11 border border-slate-200 rounded-xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras text-sm font-mono">
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Cta. Depre. Acumulada</label>
                    <input id="sw_cta_dep" placeholder="Buscar código..." class="w-full h-11 border border-slate-200 rounded-xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras text-sm font-mono">
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Cta. Gasto (5)</label>
                    <input id="sw_cta_gas" placeholder="Buscar código..." class="w-full h-11 border border-slate-200 rounded-xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras text-sm font-mono">
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '✓ Registrar Activo',
        customClass: { confirmButton: 'bg-honduras text-white px-8 py-3 rounded-2xl font-bold ml-2', cancelButton: 'bg-slate-100 text-slate-500 px-8 py-3 rounded-2xl font-bold' },
        buttonsStyling: false,
        didOpen: cargarOpcionesModal,
        preConfirm: async () => {
            const data = {
                codigo: document.getElementById('sw_codigo').value,
                nombre: document.getElementById('sw_nombre').value,
                fecha_compra: document.getElementById('sw_fecha').value,
                costo_adquisicion: document.getElementById('sw_costo').value,
                valor_salvamento: document.getElementById('sw_salvamento').value,
                vida_util_meses: document.getElementById('sw_vida').value,
                ceco_id: document.getElementById('sw_ceco').value,
                cta_act: document.getElementById('sw_cta_act').value,
                cta_dep: document.getElementById('sw_cta_dep').value,
                cta_gas: document.getElementById('sw_cta_gas').value,
            };
            if(!data.codigo || !data.nombre || !data.costo_adquisicion) {
                Swal.showValidationMessage('Faltan campos obligatorios');
                return false;
            }
            // Validar cuentas via API... (Omitiremos por simplicidad ahora y lo haremos en el service)
            // Para fines de este manual, buscaremos los IDs de las cuentas ingresadas por código
            return data;
        }
    }).then(async result => {
        if (result.isConfirmed) {
            // Primero resolver IDs de cuentas
            const payload = await prepararPayload(result.value);
            const res = await fetch('<?= BASE_URL ?>/api/activos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            if (res.ok) {
                Swal.fire({ icon:'success', title:'Activo Registrado', timer:2000, showConfirmButton:false });
                cargarActivos();
            }
        }
    });
}

async function cargarOpcionesModal() {
    const res = await fetch('<?= BASE_URL ?>/api/cecos.php');
    const json = await res.json();
    const sel = document.getElementById('sw_ceco');
    json.data?.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.innerText = `${c.codigo} - ${c.nombre}`;
        sel.appendChild(opt);
    });
}

async function prepararPayload(data) {
    // Buscar IDs de cuentas por código
    const lookCta = async (cod) => {
        const r = await fetch(`<?= BASE_URL ?>/api/cuentas.php?q=${cod}`);
        const j = await r.json();
        return j.data?.[0]?.id || null;
    };
    return {
        ...data,
        cuenta_activo_id:      await lookCta(data.cta_act),
        cuenta_deprec_acum_id: await lookCta(data.cta_dep),
        cuenta_gasto_deprec_id: await lookCta(data.cta_gas),
    };
}

function modalProcesarDepreciacion() {
    const periodos = <?= json_encode($periodos) ?>;
    if(!periodos.length) {
        Swal.fire({ icon:'warning', title:'Sin períodos abiertos', text:'Debe tener un período contable abierto para procesar depreciaciones.' });
        return;
    }

    Swal.fire({
        title: 'Depreciación Mensual',
        html: `
            <div class="text-left p-4">
                <p class="text-xs text-slate-500 mb-4 font-medium">Este proceso generará automáticamente los asientos contables de depreciación para todos los activos activos en el período seleccionado.</p>
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Seleccionar período</label>
                <select id="p_periodo" class="w-full h-11 border border-slate-200 rounded-xl px-4 outline-none text-sm font-bold mt-1">
                    ${periodos.map(p => `<option value="${p.id}">${p.mes}/${p.anio}</option>`).join('')}
                </select>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '▶ Iniciar Proceso',
        buttonsStyling: false,
        customClass: { confirmButton: 'bg-slate-900 text-white px-8 py-3 rounded-2xl font-bold ml-2', cancelButton: 'bg-slate-100 text-slate-500 px-8 py-3 rounded-2xl font-bold' },
    }).then(async result => {
        if(result.isConfirmed) {
            const pId = document.getElementById('p_periodo').value;
            Swal.fire({ title:'Procesando...', didOpen: () => Swal.showLoading() });
            
            const res = await fetch('<?= BASE_URL ?>/api/activos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'depreciate', periodo_id: pId })
            });
            const json = await res.json();
            
            if(res.ok) {
                Swal.fire({
                    icon: 'success',
                    title: 'Proceso Finalizado',
                    html: `Se procesaron <b>${json.count}</b> activos.<br>Total depreciado: <b>${fmtHNL(json.total)}</b>`
                });
                cargarActivos();
            } else {
                Swal.fire({ icon:'error', title:'Error', text: json.error });
            }
        }
    });
}

function fmtHNL(v) {
    return new Intl.NumberFormat('es-HN', { style:'currency', currency:'HNL', minimumFractionDigits:2 }).format(v||0);
}
</script>
</body>
</html>
