<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requirePermiso('comercial');

$db = Database::getInstance()->getPdo();
$eid = Auth::empresaId();

$activeNav = 'logistica'; 
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-[#f8fafc]">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <title>ContaFC – Logística y Despachos | Gestión de Entregas</title>
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
                Logística <span class="text-honduras">& Despachos</span>
            </h1>
            <p class="text-xs text-slate-400 font-bold uppercase tracking-[0.2em] mt-2">Control de Entregas y Pedidos Pendientes</p>
        </div>
        <div class="flex items-center gap-4">
             <div class="px-6 py-2 bg-slate-100 rounded-2xl flex items-center gap-4">
                 <div class="text-right border-r border-slate-200 pr-4">
                     <span class="block text-[8px] font-black text-slate-400 uppercase tracking-widest">Pendientes</span>
                     <span class="text-base font-black text-rose-500 tabular-nums" id="count-pend">0</span>
                 </div>
                 <div class="text-right">
                     <span class="block text-[8px] font-black text-slate-400 uppercase tracking-widest">En Ruta</span>
                     <span class="text-base font-black text-blue-500 tabular-nums" id="count-ruta">0</span>
                 </div>
             </div>
        </div>
    </header>

    <div class="flex-1 overflow-auto p-10">
        <!-- Dashboard Logístico -->
        <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <h3 class="text-lg font-black text-slate-800 tracking-tight">Registro de Órdenes para Despacho</h3>
                <div class="flex gap-2">
                    <button onclick="cargar('pendiente')" class="px-4 py-2 rounded-xl bg-rose-50 text-rose-600 font-black text-[10px] uppercase tracking-widest border border-rose-100">Pendientes</button>
                    <button onclick="cargar('despachado')" class="px-4 py-2 rounded-xl bg-emerald-50 text-emerald-600 font-black text-[10px] uppercase tracking-widest border border-emerald-100">Entregados</button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[10px] font-black text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100">
                            <th class="px-8 py-5">Factura / Fecha</th>
                            <th class="px-8 py-5">Cliente / Dirección</th>
                            <th class="px-8 py-5">Ítems</th>
                            <th class="px-8 py-5 text-center">Estado Logístico</th>
                            <th class="px-8 py-5 text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="logistica-body" class="divide-y divide-slate-50 italic">
                        <!-- JS items -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Modal Despacho -->
<div id="modal-despacho" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-[2.5rem] shadow-2xl overflow-hidden p-10">
        <h3 class="text-2xl font-black text-slate-800 tracking-tight mb-2 italic">Confirmar Despacho</h3>
        <p class="text-xs text-slate-500 font-medium mb-8">Registre los datos de entrega para la Factura <span id="desp-fact-num" class="font-black text-honduras"></span>.</p>
        
        <form id="form-despacho" class="space-y-5">
            <input type="hidden" id="fact_id">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Transportista / Mensajero</label>
                <input type="text" id="transportista" placeholder="Nombre de la empresa o persona..." required
                       class="w-full h-12 px-5 bg-slate-50 border border-slate-200 rounded-2xl font-bold">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Guía de Entrega / Tracking</label>
                <input type="text" id="tracking" placeholder="Opcional: N° de guía"
                       class="w-full h-12 px-5 bg-slate-50 border border-slate-200 rounded-2xl font-bold font-mono">
            </div>
            <div class="pt-6 flex gap-3">
                <button type="button" onclick="cerrarDesp()" class="flex-1 h-14 bg-slate-100 text-slate-500 font-black rounded-3xl hover:bg-slate-200 transition">CANCELAR</button>
                <button type="submit" class="flex-1 h-14 bg-honduras text-white font-black rounded-3xl hover:shadow-2xl transition-all tracking-widest">DESPACHAR ÍTEMS</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => cargar('pendiente'));

async function cargar(estado = 'pendiente') {
    const res = await fetch(`api/com-logistica.php?estado=${estado}`);
    const json = await res.json();
    const data = json.data || [];
    const body = document.getElementById('logistica-body');

    document.getElementById('count-pend').textContent = (json.counts?.pendiente || 0);
    document.getElementById('count-ruta').textContent = (json.counts?.despachado || 0);

    if (data.length === 0) {
        body.innerHTML = '<tr><td colspan="5" class="px-8 py-20 text-center text-slate-400 font-bold italic uppercase tracking-widest italic">Nada pendiente por despachar...</td></tr>';
        return;
    }

    body.innerHTML = data.map(f => `
        <tr class="hover:bg-slate-50 transition group">
            <td class="px-8 py-6">
                <span class="font-black text-slate-800 tracking-tight text-base font-mono">#${f.numero_factura}</span>
                <div class="text-[10px] text-slate-400 font-bold uppercase tracking-tight">${f.fecha}</div>
            </td>
            <td class="px-8 py-6">
                <span class="font-black text-slate-800 tracking-tight uppercase">${f.razon_social}</span>
                <div class="text-[9px] text-slate-400 font-medium truncate max-w-[250px]">${f.direccion || 'Sin dirección registrada'}</div>
            </td>
            <td class="px-8 py-6">
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-blue-50 text-honduras text-[10px] font-black border border-blue-100 tracking-tighter">
                   ${f.total_items} ÍTEMS TOTALES
                </span>
            </td>
            <td class="px-8 py-6 text-center">
                <span class="px-3 py-1 rounded-full text-[9px] font-black tracking-widest ${f.estado_logistico == 'pendiente' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700 italic'}">
                    ${f.estado_logistico.toUpperCase()}
                </span>
            </td>
            <td class="px-8 py-6 text-center">
                ${f.estado_logistico == 'pendiente' ? 
                `<button onclick="abrirDespacho(${f.id}, '${f.numero_factura}')" class="bg-dark px-4 py-2 text-white rounded-xl font-black text-[9px] uppercase tracking-widest hover:scale-105 transition-all outline-none">PROCESAR</button>` : 
                `<span class="text-[10px] text-slate-400 font-black tracking-widest italic">✓ COMPLETADO</span>`}
            </td>
        </tr>
    `).join('');
}

function abrirDespacho(id, num) {
    document.getElementById('fact_id').value = id;
    document.getElementById('desp-fact-num').textContent = num;
    document.getElementById('modal-despacho').classList.remove('hidden');
}

function cerrarDesp() { document.getElementById('modal-despacho').classList.add('hidden'); }

document.getElementById('form-despacho').addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = {
        id: document.getElementById('fact_id').value,
        transportista: document.getElementById('transportista').value,
        tracking: document.getElementById('tracking').value,
        nuevo_estado: 'despachado'
    };

    const res = await fetch('api/com-logistica.php', {
        method: 'PUT',
        body: JSON.stringify(data)
    });

    if (res.ok) {
        Swal.fire({ title: 'DESPACHADO', icon: 'success', text: 'El pedido ha pasado a estado de entrega.', confirmButtonColor: '#0073cf' });
        cerrarDesp();
        cargar('pendiente');
    }
});
</script>
</body>
</html>
