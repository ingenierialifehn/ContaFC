<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requirePermiso('comercial');

$db = Database::getInstance()->getPdo();
$eid = Auth::empresaId();

$activeNav = 'pos'; 
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-900 overflow-hidden">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <title>ContaFC – Punto de Venta (P.O.S.) | Honduras</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        honduras: '#0073cf',
                        pos_dark: '#0f172a',
                        pos_border: '#1e293b'
                    },
                    fontFamily: { sans: ['Inter','sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        .pos-btn { transition: all 0.1s; position: relative; overflow: hidden; }
        .pos-btn:active { transform: scale(0.95); opacity: 0.8; }
        .pos-item-active { background: rgba(0, 115, 207, 0.1); border-color: #0073cf; }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
        input[type="number"]::-webkit-inner-spin-button, input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    </style>
</head>
<body class="h-full font-sans flex text-slate-200">

<!-- ─── Sidebar Izquierdo (Módulos Rápidos) ────────────────────────────────── -->
<aside class="w-16 bg-slate-950 border-r border-pos_border flex flex-col items-center py-6 gap-6">
    <a href="dashboard.php" class="p-3 text-slate-500 hover:text-white transition-colors" title="Dashboard">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
    </a>
    <div class="w-10 h-10 rounded-xl bg-honduras flex items-center justify-center text-white shadow-lg shadow-blue-500/20" title="Ventas">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
    </div>
    <a href="comprobantes.php" class="p-3 text-slate-500 hover:text-white transition-colors" title="Arqueo">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
    </a>
</aside>

<!-- ─── Área Central (Búsqueda y Grid) ─────────────────────────────────────── -->
<main class="flex-1 flex flex-col min-w-0 bg-pos_dark">
    <!-- Top Bar POS -->
    <header class="h-20 bg-slate-900 border-b border-pos_border px-8 flex items-center justify-between">
        <div class="flex-1 max-w-2xl relative">
            <input type="text" id="pos-search" placeholder="F1 - Escanee o busque por Nombre/Código..." autocomplete="off"
                   class="w-full h-14 bg-slate-800 border border-pos_border rounded-2xl px-12 text-lg font-bold text-white placeholder-slate-500 focus:ring-2 focus:ring-honduras outline-none transition-all">
            <svg class="w-6 h-6 absolute left-4 top-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <!-- Resultados de búsqueda rápidos -->
            <div id="pos-search-results" class="absolute left-0 right-0 top-16 bg-slate-800 border border-pos_border shadow-2xl rounded-2xl z-50 hidden max-h-[400px] overflow-auto divide-y divide-pos_border"></div>
        </div>
        <div class="flex items-center gap-6 pl-8">
            <div class="text-right">
                <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Cliente Actual</div>
                <div class="text-white font-bold tracking-tight" id="cliente-nombre">Consumidor Final</div>
            </div>
            <button onclick="cambiarCliente()" class="w-10 h-10 rounded-xl bg-slate-800 border border-pos_border flex items-center justify-center text-slate-400 hover:text-white hover:border-slate-500 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </button>
        </div>
    </header>

    <!-- Cart Grid (High Density) -->
    <div class="flex-1 overflow-auto p-8">
        <div class="bg-slate-900/50 rounded-[2rem] border border-pos_border overflow-hidden">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-800/50 border-b border-pos_border text-[10px] font-black text-slate-500 uppercase tracking-widest">
                        <th class="px-8 py-5">Código</th>
                        <th class="px-8 py-5">Producto / Servicio</th>
                        <th class="px-8 py-5 text-center w-32">Cantidad</th>
                        <th class="px-8 py-5 text-right w-40">Precio</th>
                        <th class="px-8 py-5 text-right w-40">Total</th>
                        <th class="px-8 py-5 text-center w-20"></th>
                    </tr>
                </thead>
                <tbody id="cart-body" class="divide-y divide-pos_border font-medium">
                    <!-- Filas JS -->
                </tbody>
            </table>
            <div id="cart-empty" class="py-20 text-center text-slate-600 italic">
                <svg class="w-16 h-16 mx-auto mb-4 opacity-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Carrito de ventas vacío.
            </div>
        </div>
    </div>
</main>

<!-- ─── Panel de Pago (Derecho) ──────────────────────────────────────────────── -->
<aside class="w-[400px] bg-slate-900 border-l border-pos_border flex flex-col p-8">
    <div class="flex-1 space-y-6">
        <!-- Totales Grandes -->
        <div class="bg-slate-950 p-8 rounded-[2.5rem] border border-pos_border shadow-inner">
            <div class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2">Total a Pagar (HNL)</div>
            <div class="text-6xl font-black text-white tracking-tighter tabular-nums leading-none" id="total-pagar">L. 0.00</div>
            <div class="mt-4 pt-4 border-t border-pos_border flex justify-between text-xs opacity-60">
                <span>Subtotal Gravado</span>
                <span id="sub-total">L. 0.00</span>
            </div>
            <div class="flex justify-between text-xs opacity-60 mt-1">
                <span>ISV (15%)</span>
                <span id="tax-total">L. 0.00</span>
            </div>
        </div>

        <!-- Métodos de Pago -->
        <div>
            <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-4 italic">Medio de Pago Principal</div>
            <div class="grid grid-cols-2 gap-3">
                <button class="pos-btn bg-slate-800 border-2 border-transparent p-4 rounded-2xl flex flex-col items-center gap-2 group hover:border-honduras transition-colors" onclick="seleccionarPago('efectivo')">
                   <div class="w-10 h-10 rounded-xl bg-slate-700 flex items-center justify-center text-emerald-400"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                   <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 group-hover:text-white transition-colors">Efectivo</span>
                </button>
                <button class="pos-btn bg-slate-800 border-2 border-transparent p-4 rounded-2xl flex flex-col items-center gap-2 group hover:border-blue-500 transition-colors" onclick="seleccionarPago('tarjeta')">
                   <div class="w-10 h-10 rounded-xl bg-slate-700 flex items-center justify-center text-blue-400"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg></div>
                   <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 group-hover:text-white transition-colors">Tarjeta</span>
                </button>
            </div>
        </div>

        <!-- Calculadora de Cambio Rápida -->
        <div class="bg-slate-800/50 p-6 rounded-3xl border border-pos_border">
            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block italic">Paga con:</label>
            <input type="number" id="paga-con" placeholder="L. 0.00" oninput="calcCambio()"
                   class="w-full bg-slate-900 border border-pos_border rounded-2xl h-14 px-6 text-2xl font-black text-honduras tabular-nums outline-none focus:ring-2 focus:ring-honduras transition-all">
            <div class="mt-4 flex justify-between items-center px-2">
                <span class="text-xs font-bold text-slate-500">SU CAMBIO:</span>
                <span class="text-xl font-black text-emerald-400 tabular-nums" id="res-cambio">L. 0.00</span>
            </div>
        </div>
    </div>

    <!-- Botón de Acción Principal -->
    <button onclick="finalizarVenta()" class="pos-btn w-full mt-8 py-6 bg-honduras text-white rounded-[2rem] font-black text-lg shadow-2xl shadow-blue-500/20 hover:scale-[1.02] flex items-center justify-center gap-3 active:scale-95">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
        IMPRIMIR FACTURA (F12)
    </button>
</aside>

<!-- Modal Trazabilidad (Lotes/Seriales) -->
<div id="modal-traza" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-[100] hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 w-full max-w-sm rounded-[2.5rem] border border-pos_border p-10">
        <h3 class="text-2xl font-black text-white tracking-tight mb-2 italic">Trazabilidad Requerida</h3>
        <p class="text-xs text-slate-500 font-medium mb-8">Seleccione un Lote o Serial para descontar del stock físico.</p>
        <div id="traza-list" class="space-y-3 max-h-[300px] overflow-auto"></div>
        <button onclick="cerrarTraza()" class="w-full mt-8 py-4 bg-slate-800 text-slate-400 rounded-2xl font-black text-xs uppercase tracking-widest">Cancelar</button>
    </div>
</div>

<script>
let cart = [];
let clienteId = 1; // Default Consumidor Final
let total = 0;

// Al cargar enfocar buscador
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('pos-search').focus();
    setupHotkeys();
});

function setupHotkeys() {
    window.addEventListener('keydown', (e) => {
        if (e.key === 'F1') { e.preventDefault(); document.getElementById('pos-search').focus(); }
        if (e.key === 'F12') { e.preventDefault(); finalizarVenta(); }
    });
}

// ─── Búsqueda de Productos ────────────────────────────────────────────────
const searchInput = document.getElementById('pos-search');
const resultsBox = document.getElementById('pos-search-results');

searchInput.addEventListener('input', async (e) => {
    const q = e.target.value.trim();
    if (q.length < 2) { resultsBox.classList.add('hidden'); return; }

    const res = await fetch('api/com-productos.php');
    const json = await res.json();
    const data = (json.data || []).filter(p => p.nombre.toLowerCase().includes(q.toLowerCase()) || p.codigo.toLowerCase().includes(q.toLowerCase()));

    if (data.length > 0) {
        resultsBox.innerHTML = data.map(p => `
            <div onclick='addToCart(${JSON.stringify(p)})' class="px-6 py-4 hover:bg-slate-700 cursor-pointer flex justify-between items-center group transition-colors">
                <div>
                   <span class="block text-xs font-black text-slate-500 uppercase group-hover:text-honduras transition-colors">${p.codigo}</span>
                   <span class="font-bold text-white text-base">${p.nombre}</span>
                </div>
                <span class="text-xl font-black text-emerald-400 tabular-nums">${fmt(p.precio_venta)}</span>
            </div>
        `).join('');
        resultsBox.classList.remove('hidden');
    } else {
        resultsBox.innerHTML = '<div class="p-8 text-center text-slate-500 font-bold uppercase tracking-widest italic">No se encontró el ítem</div>';
        resultsBox.classList.remove('hidden');
    }
});

function addToCart(p) {
    resultsBox.classList.add('hidden');
    searchInput.value = '';
    
    // Si maneja lotes/seriales, abrir modal
    if (p.maneja_lotes == 1 || p.maneja_seriales == 1) {
        openTraza(p);
        return;
    }

    const existIdx = cart.findIndex(item => item.id === p.id);
    if (existIdx > -1) {
        cart[existIdx].cant++;
    } else {
        cart.push({ id: p.id, codigo: p.codigo, nombre: p.nombre, cant: 1, precio: parseFloat(p.precio_venta), isv: parseFloat(p.tasa_isv) });
    }
    renderCart();
    document.getElementById('pos-search').focus();
}

function renderCart() {
    const body = document.getElementById('cart-body');
    const empty = document.getElementById('cart-empty');
    if (cart.length === 0) { body.innerHTML = ''; empty.classList.remove('hidden'); updateTotals(); return; }
    
    empty.classList.add('hidden');
    body.innerHTML = cart.map((p, i) => `
        <tr class="hover:bg-slate-800/20 group">
            <td class="px-8 py-5 font-mono text-slate-500 text-[10px] font-black uppercase tracking-tighter">${p.codigo}</td>
            <td class="px-8 py-5">
                <span class="font-bold text-slate-100 text-sm italic">${p.nombre}</span>
                ${p.traza_valor ? `<span class="block text-[9px] font-black text-honduras mt-1 uppercase tracking-widest">[ID: ${p.traza_valor}]</span>` : ''}
            </td>
            <td class="px-8 py-5 text-center">
                <div class="flex items-center justify-center gap-4">
                    <button onclick="updateQty(${i},-1)" class="w-8 h-8 rounded-lg bg-slate-800 text-slate-400 hover:text-white transition-colors">-</button>
                    <span class="font-black text-lg tabular-nums text-white w-8">${p.cant}</span>
                    <button onclick="updateQty(${i},1)" class="w-8 h-8 rounded-lg bg-slate-800 text-slate-400 hover:text-white transition-colors">+</button>
                </div>
            </td>
            <td class="px-8 py-5 text-right font-mono font-bold text-slate-400">${fmt(p.precio)}</td>
            <td class="px-8 py-5 text-right font-black text-white text-base font-mono tracking-tighter">${fmt(p.precio * p.cant)}</td>
            <td class="px-8 py-5 text-center">
                <button onclick="removeItem(${i})" class="text-slate-600 hover:text-rose-500 transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
            </td>
        </tr>
    `).join('');
    updateTotals();
}

function updateQty(i, delta) {
    cart[i].cant += delta;
    if (cart[i].cant < 1) cart.splice(i, 1);
    renderCart();
}

function removeItem(i) { cart.splice(i, 1); renderCart(); }

function updateTotals() {
    let sub = 0, isv = 0;
    cart.forEach(p => {
        const lineaBase = p.cant * p.precio;
        sub += lineaBase;
        isv += lineaBase * (p.isv / 100);
    });
    total = sub + isv;
    document.getElementById('sub-total').textContent = fmt(sub);
    document.getElementById('tax-total').textContent = fmt(isv);
    document.getElementById('total-pagar').textContent = fmt(total);
    calcCambio();
}

function calcCambio() {
    const paga = parseFloat(document.getElementById('paga-con').value) || 0;
    const cambio = Math.max(0, paga - total);
    document.getElementById('res-cambio').textContent = fmt(cambio);
}

function fmt(n) { return new Intl.NumberFormat('es-HN', { style:'currency', currency:'HNL' }).format(n); }

// ─── Trazabilidad (Lotes/Seriales) ────────────────────────────────────────
async function openTraza(p) {
    const res = await fetch(`api/com-trazabilidad.php?producto_id=${p.id}`);
    const json = await res.json();
    const data = json.data || [];
    const modal = document.getElementById('modal-traza');
    const container = document.getElementById('traza-list');

    if (data.length === 0) {
        Swal.fire('Sin Stock', 'Este producto requiere trazabilidad pero no tiene lotes/seriales disponibles.', 'error');
        return;
    }

    container.innerHTML = data.map(t => `
        <div onclick='selectTraza(${JSON.stringify(p)}, ${JSON.stringify(t)})' class="p-5 bg-slate-800 border border-pos_border rounded-3xl hover:border-honduras cursor-pointer group transition-all">
            <div class="flex justify-between items-center mb-1">
                <span class="text-xs font-black text-slate-500 uppercase tracking-widest">${t.tipo}</span>
                <span class="px-2 py-0.5 bg-emerald-500/10 text-emerald-400 text-[10px] font-black rounded-lg">Stk: ${t.stock_actual}</span>
            </div>
            <div class="font-black text-white text-lg tracking-tighter uppercase tabular-nums">${t.valor}</div>
            <div class="text-[9px] text-slate-500 font-bold uppercase mt-1">Vence: ${t.fecha_vence || 'N/A'}</div>
        </div>
    `).join('');
    modal.classList.remove('hidden');
}

function selectTraza(p, t) {
    cart.push({ ...p, precio: parseFloat(p.precio_venta), isv: parseFloat(p.tasa_isv), traza_id: t.id, traza_valor: t.valor });
    cerrarTraza();
    renderCart();
}

function cerrarTraza() { document.getElementById('modal-traza').classList.add('hidden'); }

// ─── Finalizar Venta ──────────────────────────────────────────────────────
async function finalizarVenta() {
    if (cart.length === 0) { Swal.fire('POS Vacío', 'Agregue productos antes de cobrar.', 'warning'); return; }

    const { isConfirmed } = await Swal.fire({
        title: 'Confirmar Venta',
        text: `Se emitirá factura por ${fmt(total)}`,
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#0073cf',
        showCancelButton: true
    });

    if (!isConfirmed) return;

    const data = {
        cliente_id: clienteId,
        fecha: new Date().toISOString().substring(0,10),
        tipo_pago: 'contado',
        lineas: cart.map(item => ({
            p_id: item.id,
            cant: item.cant,
            precio: item.precio,
            isv: item.isv,
            subtotal: item.cant * item.precio,
            total_isv: (item.cant * item.precio) * (item.isv / 100),
            traza_id: item.traza_id
        }))
    };

    const res = await fetch('api/com-facturas.php', {
        method: 'POST',
        body: JSON.stringify(data)
    });
    const json = await res.json();

    if (json.success) {
        Swal.fire({ title: 'COMPLETADO', text: 'Venta registrada con éxito.', icon: 'success', timer: 1500, showConfirmButton: false });
        cart = [];
        renderCart();
        document.getElementById('paga-con').value = '';
        document.getElementById('pos-search').focus();
    } else {
        Swal.fire('Error', json.error, 'error');
    }
}
</script>
</body>
</html>
