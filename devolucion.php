<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requirePermiso('comercial');

$activeNav = 'devoluciones'; 
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <title>ContaFC – Devoluciones y Notas de Crédito</title>
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
                Notas de <span class="text-rose-500">Crédito</span>
            </h1>
            <p class="text-xs text-slate-400 font-bold uppercase tracking-[0.2em] mt-2">Devoluciones y Ajustes Comerciales</p>
        </div>
        <button onclick="nuevaNC()" 
                class="bg-rose-500 px-8 py-3 text-white rounded-2xl font-black tracking-widest hover:scale-105 transition-all shadow-xl shadow-rose-500/20 flex items-center gap-2">
            NUEVA DEVOLUCIÓN
        </button>
    </header>

    <div class="flex-1 overflow-auto p-10">
        <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
             <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-100 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                        <th class="px-8 py-5">NC Numero</th>
                        <th class="px-8 py-5 text-honduras">Referencia Factura</th>
                        <th class="px-8 py-5">Cliente</th>
                        <th class="px-8 py-5 text-right w-40">Total Retornado</th>
                        <th class="px-8 py-5 text-center">Contabilidad</th>
                    </tr>
                </thead>
                <tbody id="nc-body" class="divide-y divide-slate-50 italic">
                    <!-- JS items -->
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Modal Nota Credito -->
<div id="modal-nc" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-2xl rounded-[2.5rem] shadow-2xl overflow-hidden p-10">
        <h3 class="text-2xl font-black text-slate-800 tracking-tight mb-2 italic">Emitir Nota de Crédito</h3>
        <p class="text-xs text-slate-500 font-medium mb-8">Seleccione la factura para aplicar la devolución total o parcial.</p>
        
        <form id="form-nc" class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">N° Factura SAR</label>
                    <input type="text" id="busc-factura" placeholder="000-001..." required oninput="buscarFact(this.value)"
                           class="w-full h-12 px-5 bg-slate-50 border border-slate-200 rounded-2xl font-bold font-mono">
                    <div id="res-busc-fact" class="absolute bg-white shadow-2xl border rounded-xl z-20 hidden"></div>
                    <input type="hidden" id="fact_id">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Fecha Devolución</label>
                    <input type="date" id="fecha" value="<?= date('Y-m-d') ?>" 
                           class="w-full h-12 px-5 bg-slate-50 border border-slate-200 rounded-2xl font-bold">
                </div>
            </div>
            
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Motivo del Ajuste</label>
                <select id="motivo" class="w-full h-12 px-5 bg-slate-50 border border-slate-200 rounded-2xl font-bold">
                    <option value="1">Reingreso de Mercancía (Stock)</option>
                    <option value="2">Descuento no Aplicado</option>
                    <option value="3">Error en Precio</option>
                    <option value="4">Producto Dañado</option>
                </select>
            </div>

            <div class="pt-6 border-t border-slate-100 flex gap-4">
                <button type="button" onclick="cerrarNC()" class="flex-1 h-14 bg-slate-100 text-slate-500 font-black rounded-3xl hover:bg-slate-200 transition">CANCELAR</button>
                <button type="submit" class="flex-1 h-14 bg-rose-500 text-white font-black rounded-3xl hover:shadow-2xl transition-all tracking-widest">EMITIR NOTA DE CRÉDITO</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', cargarNC);

async function cargarNC() {
    const res = await fetch('api/com-devoluciones.php');
    const json = await res.json();
    const data = json.data || [];
    const body = document.getElementById('nc-body');

    if (data.length === 0) {
        body.innerHTML = '<tr><td colspan="5" class="px-8 py-20 text-center text-slate-400 font-bold italic uppercase tracking-widest italic">Cero notas de crédito registradas...</td></tr>';
        return;
    }

    body.innerHTML = data.map(nc => `
        <tr class="hover:bg-rose-50/30 transition group">
            <td class="px-8 py-6 font-black text-rose-500 text-base font-mono">NC-${String(nc.id).padStart(6, '0')}</td>
            <td class="px-8 py-6">
                <span class="text-[10px] text-slate-400 font-black uppercase tracking-widest block">Ref. Original</span>
                <span class="font-bold text-slate-800 font-mono">${nc.numero_factura}</span>
            </td>
            <td class="px-8 py-6">
                <span class="font-black text-slate-700 tracking-tight italic uppercase">${nc.razon_social}</span>
            </td>
             <td class="px-8 py-6 text-right font-black text-rose-600 text-lg tabular-nums">
                L. ${parseFloat(nc.total_nc).toLocaleString()}
            </td>
            <td class="px-8 py-6 text-center">
                <span class="text-[9px] font-black text-emerald-600 bg-emerald-50 px-3 py-1 rounded-lg border border-emerald-100 tracking-widest uppercase">CONTABILIZADO ✓</span>
            </td>
        </tr>
    `).join('');
}

function nuevaNC() { document.getElementById('modal-nc').classList.remove('hidden'); }
function cerrarNC() { document.getElementById('modal-nc').classList.add('hidden'); }

async function buscarFact(q) {
    if (q.length < 4) { document.getElementById('res-busc-fact').classList.add('hidden'); return; }
    // En producción iría a API filtrada
    const res = await fetch(`api/com-facturas-search.php?q=${q}`);
    const json = await res.json();
    const caja = document.getElementById('res-busc-fact');
    caja.innerHTML = (json.data || []).map(f => `
        <div onclick="selecFact(${f.id}, '${f.numero_factura}')" class="px-6 py-4 hover:bg-slate-50 cursor-pointer border-b">
            <span class="font-black text-slate-800">${f.numero_factura}</span><br>
            <span class="text-[10px] text-slate-400 uppercase font-black">${f.razon_social} | L. ${f.total}</span>
        </div>
    `).join('');
    caja.classList.remove('hidden');
}

function selecFact(id, num) {
    document.getElementById('fact_id').value = id;
    document.getElementById('busc-factura').value = num;
    document.getElementById('res-busc-fact').classList.add('hidden');
}

document.getElementById('form-nc').addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = {
        factura_id: document.getElementById('fact_id').value,
        fecha: document.getElementById('fecha').value,
        motivo: document.getElementById('motivo').value
    };

    Swal.fire({ title: 'Reversando Transacción...', text: 'Actualizando Stock y Contabilidades', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    const res = await fetch('api/com-devoluciones.php', {
        method: 'POST',
        body: JSON.stringify(data)
    });

    if (res.ok) {
        Swal.fire('Éxito', 'Nota de Crédito emitida. El inventario ha sido devuelto a stock.', 'success');
        cerrarNC();
        cargarNC();
    }
});
</script>
</body>
</html>
