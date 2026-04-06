<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requirePermiso('comercial');

$activeNav = 'contratos'; 
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <title>ContaFC – Contratos de Facturación Recurrente</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { honduras: '#0073cf', dark: '#0f172a' },
                    fontFamily: { sans: ['Inter','sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body class="h-full font-sans flex text-sm overflow-hidden bg-slate-50/50">

<?php require __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-1 flex flex-col min-w-0">
    <header class="bg-white border-b border-slate-200 px-10 py-8 flex items-center justify-between z-10">
        <div>
            <h1 class="text-3xl font-black text-slate-800 tracking-tight leading-none italic">
                Contratos <span class="text-honduras">& Suscripciones</span>
            </h1>
            <p class="text-xs text-slate-400 font-bold uppercase tracking-[0.2em] mt-2">Gestión de Facturación Recurrente Mensual</p>
        </div>
        <div class="flex items-center gap-4">
             <button onclick="generarMasivo()" class="bg-emerald-500 px-6 py-3 text-white rounded-2xl font-black text-[10px] tracking-widest hover:scale-105 transition-all shadow-xl shadow-emerald-500/20 flex items-center gap-2">
                PROCESAR RECURRENCIA (MASIVO)
            </button>
            <button onclick="nuevoContrato()" class="bg-dark px-6 py-3 text-white rounded-2xl font-black text-[10px] tracking-widest hover:scale-105 transition-all shadow-xl shadow-slate-900/20 flex items-center gap-2">
                NUEVO CONTRATO
            </button>
        </div>
    </header>

    <div class="flex-1 overflow-auto p-10">
        <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
             <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-100 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                        <th class="px-8 py-5">Contrato</th>
                        <th class="px-8 py-5">Cliente</th>
                        <th class="px-8 py-5">Servicio Recurrente</th>
                        <th class="px-8 py-5 text-right">Mensualidad</th>
                        <th class="px-8 py-5 text-center">Sig. Facturacion</th>
                        <th class="px-8 py-5 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody id="contratos-body" class="divide-y divide-slate-50 italic">
                    <!-- JS items -->
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Modal Contrato -->
<div id="modal-contrato" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-xl rounded-[2.5rem] shadow-2xl overflow-hidden p-10">
        <h3 class="text-2xl font-black text-slate-800 tracking-tight mb-2 italic">Nuevo Contrato / Suscripción</h3>
        <p class="text-xs text-slate-500 font-medium mb-8">Defina los parámetros para la facturación automática mensual.</p>
        
        <form id="form-contrato" class="space-y-6">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Cliente</label>
                <div class="relative">
                   <input type="text" id="busc-clie-contr" placeholder="Buscar cliente..." oninput="buscarClie(this.value)"
                          class="w-full h-12 px-5 bg-slate-50 border border-slate-200 rounded-2xl font-bold">
                    <div id="res-busc-clie" class="absolute left-0 right-0 top-full mt-2 bg-white shadow-2xl border border-slate-100 z-50 rounded-2xl max-h-60 overflow-y-auto hidden"></div>
                   <input type="hidden" id="c_cliente_id">
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                   <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Servicio / Ítem</label>
                   <select id="c_producto_id" class="w-full h-12 px-5 bg-slate-50 border border-slate-200 rounded-2xl font-bold">
                       <!-- JS items -->
                   </select>
                </div>
                <div>
                   <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Monto Mensual</label>
                   <input type="number" step="0.01" id="c_monto" placeholder="0.00" required
                          class="w-full h-12 px-5 bg-slate-50 border border-slate-200 rounded-2xl font-black text-honduras">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Día de Facturación</label>
                    <input type="number" min="1" max="28" id="c_dia" value="1" 
                           class="w-full h-12 px-5 bg-slate-50 border border-slate-200 rounded-2xl font-bold">
                </div>
                 <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Fecha Inicio</label>
                    <input type="date" id="c_inicio" value="<?= date('Y-m-d') ?>" 
                           class="w-full h-12 px-5 bg-slate-50 border border-slate-200 rounded-2xl font-bold">
                </div>
            </div>

            <div class="pt-6 border-t border-slate-100 flex gap-4">
                <button type="button" onclick="cerrarC()" class="flex-1 h-14 bg-slate-100 text-slate-500 font-black rounded-3xl hover:bg-slate-200 transition">CANCELAR</button>
                <button type="submit" class="flex-1 h-14 bg-honduras text-white font-black rounded-3xl hover:shadow-2xl transition-all tracking-widest">CREAR CONTRATO</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => { cargarContratos(); cargarServicios(); });

async function cargarContratos() {
    const res = await fetch('api/com-contratos.php');
    const json = await res.json();
    const data = json.data || [];
    const body = document.getElementById('contratos-body');

    body.innerHTML = data.map(c => `
        <tr class="hover:bg-slate-50/80 transition group">
            <td class="px-8 py-6">
                <span class="font-black text-slate-800 tracking-tight text-sm uppercase">CONTR-${String(c.id).padStart(5, '0')}</span>
            </td>
            <td class="px-8 py-6">
                <span class="font-bold text-slate-700 uppercase">${c.razon_social}</span>
            </td>
            <td class="px-8 py-6">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-tighter">${c.nombre_prod}</span>
            </td>
            <td class="px-8 py-6 text-right font-black text-slate-900 tabular-nums">
                L. ${parseFloat(c.monto).toLocaleString()}
            </td>
            <td class="px-8 py-6 text-center">
                <span class="px-3 py-1 bg-blue-50 text-honduras text-[9px] font-black rounded-lg border border-blue-100">DÍA ${c.dia_facturacion}</span>
            </td>
            <td class="px-8 py-6 text-center">
                <button onclick="eliminarContrato(${c.id})" class="text-slate-300 hover:text-rose-500 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
            </td>
        </tr>
    `).join('');
}

async function cargarServicios() {
    const res = await fetch('api/com-productos.php');
    const json = await res.json();
    const selects = document.getElementById('c_producto_id');
    selects.innerHTML = (json.data || []).map(p => `<option value="${p.id}">${p.nombre}</option>`).join('');
}

function nuevoContrato() { 
    document.getElementById('form-contrato').reset();
    document.getElementById('c_cliente_id').value = '';
    document.getElementById('modal-contrato').classList.remove('hidden'); 
}
function cerrarC() { document.getElementById('modal-contrato').classList.add('hidden'); }

async function buscarClie(q) {
    const caja = document.getElementById('res-busc-clie');
    if (q.length < 1) { caja.classList.add('hidden'); return; }
    const res = await fetch(`api/terceros.php?q=${q}&tipo=cliente`);
    const json = await res.json();
    const data = json.data || [];
    
    if (data.length === 0) {
        caja.innerHTML = '<div class="px-6 py-4 text-slate-400 italic">No se encontraron clientes...</div>';
    } else {
        caja.innerHTML = data.map(t => `
            <div onclick="selecClie(${t.id}, '${t.razon_social || t.nombre}')" 
                 class="px-6 py-3 hover:bg-slate-50 cursor-pointer border-b border-slate-100 text-xs flex justify-between items-center group">
                <span class="font-black text-slate-700 group-hover:text-honduras transition-colors">${t.razon_social || t.nombre}</span>
                <span class="text-[9px] font-bold text-slate-300 uppercase tracking-widest">${t.nit_cc}</span>
            </div>
        `).join('');
    }
    caja.classList.remove('hidden');
}

function selecClie(id, nom) {
    document.getElementById('c_cliente_id').value = id;
    document.getElementById('busc-clie-contr').value = nom;
    document.getElementById('res-busc-clie').classList.add('hidden');
}

// ELIMINAR
async function eliminarContrato(id) {
    const result = await Swal.fire({
        title: '¿Eliminar contrato?',
        text: "Se detendrá la facturación recurrente para este cliente.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        confirmButtonText: 'Sí, eliminar'
    });
    if (result.isConfirmed) {
        const res = await fetch(`api/com-contratos.php?id=${id}`, { method: 'DELETE' });
        if (res.ok) {
            Swal.fire('Eliminado', 'El contrato ha sido removido.', 'success');
            cargarContratos();
        }
    }
}

document.getElementById('form-contrato').addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = {
        cliente_id: document.getElementById('c_cliente_id').value,
        producto_id: document.getElementById('c_producto_id').value,
        monto: document.getElementById('c_monto').value,
        dia: document.getElementById('c_dia').value,
        fecha_inicio: document.getElementById('c_inicio').value
    };

    const res = await fetch('api/com-contratos.php', { 
        method: 'POST', 
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data) 
    });
    if (res.ok) { 
        Swal.fire('Guardado', 'Suscripción activa para facturación masiva.', 'success'); 
        cerrarC(); 
        cargarContratos(); 
    } else {
        Swal.fire('Error', 'No se pudo guardar el contrato.', 'error');
    }
});

async function generarMasivo() {
    const { isConfirmed } = await Swal.fire({
        title: 'Facturación Masiva',
        text: 'Se generarán facturas para todos los contratos pendientes del mes actual. ¿Proceder?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, Facturar Todo',
        confirmButtonColor: '#10b981'
    });

    if (!isConfirmed) return;

    Swal.fire({ title: 'Procesando...', text: 'Emitiendo facturas SAR masivas', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
        const res = await fetch('api/com-contratos-masivo.php', { method: 'POST' });
        const json = await res.json();

        if (json.success) {
            Swal.fire('Proceso Completado', `Se generaron ${json.count} facturas automáticamente.`, 'success');
            cargarContratos();
        } else {
            Swal.fire('Error', json.error || 'Error desconocido', 'error');
        }
    } catch (e) {
        Swal.fire('Error', 'Fallo en la comunicación con el servidor. Verifique si la tabla existe.', 'error');
    }
}
</script>
</body>
</html>
