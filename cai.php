<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requirePermiso('facturacion');

$db = Database::getInstance()->getPdo();
$eid = Auth::empresaId();
$empresa = $db->query("SELECT * FROM empresas WHERE id = $eid")->fetch();

$activeNav = 'config_cai'; 
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ContaFC – Resoluciones CAI | SAR Honduras</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        honduras: '#0073cf',
                        dark: '#0f172a'
                    },
                    fontFamily: { sans: ['Inter','sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="h-full font-sans flex text-sm">

<?php require __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <!-- Header -->
    <header class="bg-white border-b border-slate-200 px-8 py-5 flex items-center justify-between z-10">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">Resoluciones <span class="text-honduras">SAR (CAI)</span></h1>
            <p class="text-xs text-slate-500 font-medium uppercase tracking-widest mt-1">Configuración de rangos de facturación vigente</p>
        </div>
        <button onclick="nuevoCAI()" 
                class="bg-honduras px-6 py-2.5 text-white rounded-xl font-bold hover:shadow-lg hover:shadow-blue-500/30 transition-all flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            AGREGAR RESOLUCIÓN
        </button>
    </header>

    <div class="flex-1 overflow-auto p-8">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden min-w-[800px]">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                        <th class="px-6 py-4">Punto Emisión</th>
                        <th class="px-6 py-4">Establecimiento</th>
                        <th class="px-6 py-4">Código CAI</th>
                        <th class="px-6 py-4">Rango Autorizado</th>
                        <th class="px-6 py-4">Uso Actual</th>
                        <th class="px-6 py-4">Fecha Límite</th>
                        <th class="px-6 py-4 text-center">Estado</th>
                        <th class="px-6 py-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="cai-body" class="divide-y divide-slate-50">
                    <!-- JS items -->
                </tbody>
            </table>
        </div>
        
        <!-- Alerta de Vencimiento CAI (SAR Compliance) -->
        <div id="cai-alert" class="mt-8 hidden p-6 bg-rose-50 border-2 border-rose-200 rounded-2xl flex items-center gap-5">
            <div class="w-12 h-12 bg-rose-500 text-white rounded-full flex items-center justify-center flex-shrink-0 animate-pulse">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                <h4 class="text-rose-800 font-black text-lg leading-tight uppercase tracking-tighter">¡Advertencia SAR!</h4>
                <p class="text-rose-600 font-medium">Tienes resoluciones próximas a vencer o sin correlativo disponible. Evita multas y actualiza tus rangos de facturación.</p>
            </div>
        </div>
    </div>
</main>

<!-- Modal CAI -->
<div id="modal-cai" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-xl font-black text-slate-800 tracking-tight" id="modal-title">Configurar CAI</h3>
            <button onclick="cerrarModal()" class="text-slate-400 hover:text-slate-600 transition"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="form-cai" class="p-8 space-y-5">
            <input type="hidden" id="cai_id">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Establecimiento</label>
                    <input type="text" id="establecimiento" placeholder="000" maxlength="3" required
                           class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-honduras focus:border-honduras outline-none transition-all font-mono font-bold">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Punto Emisión</label>
                    <input type="text" id="punto_emision" placeholder="001" maxlength="3" required
                           class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-honduras focus:border-honduras outline-none transition-all font-mono font-bold">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Código CAI (Resolución)</label>
                <input type="text" id="cai" placeholder="XXXXXX-XXXXXX-XXXXXX..." required
                       class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-honduras focus:border-honduras outline-none transition-all font-mono">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Rango Desde</label>
                    <input type="number" id="rango_desde" placeholder="1" required
                           class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-honduras outline-none font-mono">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Rango Hasta</label>
                    <input type="number" id="rango_hasta" placeholder="1000" required
                           class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-honduras outline-none font-mono">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Cons. Actual</label>
                    <input type="number" id="consecutivo_actual" placeholder="0" required
                           class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-honduras outline-none font-mono">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Fecha Límite</label>
                    <input type="date" id="fecha_limite" required
                           class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-honduras outline-none">
                </div>
            </div>
            <div class="pt-4 flex gap-3">
                <button type="button" onclick="cerrarModal()" class="flex-1 h-12 bg-slate-100 text-slate-500 font-bold rounded-xl hover:bg-slate-200 transition">CANCELAR</button>
                <button type="submit" class="flex-1 h-12 bg-dark text-white font-black rounded-xl hover:shadow-xl transition-all">GUARDAR RESOLUCIÓN</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', cargarCAI);

async function cargarCAI() {
    try {
        const res = await fetch('api/com-cai.php');
        const json = await res.json();
        const body = document.getElementById('cai-body');
        
        if (!json.data || json.data.length === 0) {
            body.innerHTML = '<tr><td colspan="8" class="px-6 py-12 text-center text-slate-400 font-bold italic">Sin resoluciones del SAR configuradas.</td></tr>';
            return;
        }

        body.innerHTML = json.data.map(r => {
            const vencido = new Date(r.fecha_limite) < new Date();
            const proximo = !vencido && (new Date(r.fecha_limite) - new Date()) / (1000*60*60*24) < 30;
            if (vencido || proximo) document.getElementById('cai-alert').classList.remove('hidden');

            return `
                <tr class="hover:bg-slate-50/80 transition group">
                    <td class="px-6 py-5 font-black text-slate-700 font-mono tracking-tighter">${r.punto_emision}</td>
                    <td class="px-6 py-5 font-black text-slate-700 font-mono tracking-tighter">${r.establecimiento}</td>
                    <td class="px-6 py-5">
                        <div class="text-[11px] font-black text-slate-500 font-mono">${r.cai}</div>
                        <div class="text-[9px] text-slate-400 uppercase font-black">Código de Autorización</div>
                    </td>
                    <td class="px-6 py-5 font-mono text-slate-600">${fmtNum(r.rango_desde)} al ${fmtNum(r.rango_hasta)}</td>
                    <td class="px-6 py-5">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden w-24">
                                <div class="h-full bg-honduras" style="width: ${(r.consecutivo_actual / r.rango_hasta * 100)}%"></div>
                            </div>
                            <span class="font-black text-slate-900">${r.consecutivo_actual}</span>
                        </div>
                    </td>
                    <td class="px-6 py-5 font-bold ${vencido ? 'text-rose-500' : proximo ? 'text-amber-500' : 'text-slate-600'}">
                        ${r.fecha_limite}
                        ${vencido ? '<span class="block text-[8px] font-black uppercase">¡VENCIDO!</span>' : ''}
                    </td>
                    <td class="px-6 py-5 text-center">
                        <span class="px-3 py-1 rounded-full text-[9px] font-black tracking-widest ${r.activo ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400'}">
                            ${r.activo ? 'ACTIVO' : 'INACTIVO'}
                        </span>
                    </td>
                    <td class="px-6 py-5 text-center">
                        <button onclick='editarCAI(${JSON.stringify(r)})' class="p-2 text-slate-400 hover:text-honduras transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></button>
                    </td>
                </tr>
            `;
        }).join('');
    } catch (e) {
        console.error(e);
    }
}

function fmtNum(n) { return String(n).padStart(8, '0'); }

const modal = document.getElementById('modal-cai');
function nuevoCAI() {
    document.getElementById('form-cai').reset();
    document.getElementById('cai_id').value = '';
    document.getElementById('modal-title').textContent = 'Nueva Resolución SAR';
    modal.classList.remove('hidden');
}

function cerrarModal() { modal.classList.add('hidden'); }

function editarCAI(r) {
    document.getElementById('cai_id').value = r.id;
    document.getElementById('establecimiento').value = r.establecimiento;
    document.getElementById('punto_emision').value = r.punto_emision;
    document.getElementById('cai').value = r.cai;
    document.getElementById('rango_desde').value = r.rango_desde;
    document.getElementById('rango_hasta').value = r.rango_hasta;
    document.getElementById('consecutivo_actual').value = r.consecutivo_actual;
    document.getElementById('fecha_limite').value = r.fecha_limite;
    document.getElementById('modal-title').textContent = 'Editar Resolución';
    modal.classList.remove('hidden');
}

document.getElementById('form-cai').addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = {
        id: document.getElementById('cai_id').value,
        establecimiento: document.getElementById('establecimiento').value,
        punto_emision: document.getElementById('punto_emision').value,
        cai: document.getElementById('cai').value,
        rango_desde: document.getElementById('rango_desde').value,
        rango_hasta: document.getElementById('rango_hasta').value,
        consecutivo_actual: document.getElementById('consecutivo_actual').value,
        fecha_limite: document.getElementById('fecha_limite').value
    };

    const res = await fetch('api/com-cai.php', {
        method: data.id ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });

    if (res.ok) {
        Swal.fire('Éxito', 'Resolución guardada correctamente', 'success');
        cerrarModal();
        cargarCAI();
    } else {
        Swal.fire('Error', 'No se pudo guardar la resolución', 'error');
    }
});
</script>
</body>
</html>
