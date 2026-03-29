<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requirePermiso('comercial');

$db = Database::getInstance()->getPdo();
$eid = Auth::empresaId();

$activeNav = 'productos'; 
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-[#f8fafc]">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <title>ContaFC – Catálogo de Productos y Servicios</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        honduras: '#0073cf',
                        dark: '#1e293b'
                    },
                    fontFamily: { sans: ['Inter','system-ui','sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        .product-card { transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); }
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1); }
        .grid-dense { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
    </style>
</head>
<body class="h-full font-sans flex text-sm overflow-hidden bg-slate-50/50">

<?php require __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-1 flex flex-col min-w-0">
    <!-- Top Header -->
    <header class="bg-white border-b border-slate-200 px-10 py-6 flex items-center justify-between z-10">
        <div>
            <h1 class="text-3xl font-black text-slate-800 tracking-tight leading-none italic">
                Catálogo <span class="text-honduras">Comercial</span>
            </h1>
            <p class="text-xs text-slate-400 font-bold uppercase tracking-[0.2em] mt-2">Productos, Servicios y Kits</p>
        </div>
        <div class="flex items-center gap-4">
            <div class="relative">
                <input type="text" id="buscador" oninput="filtrar()" placeholder="Buscar SKU o Nombre..." 
                       class="h-11 w-64 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-honduras outline-none transition-all">
                <svg class="w-5 h-5 absolute left-4 top-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <button onclick="nuevoProducto()" 
                    class="bg-dark px-8 py-3 text-white rounded-2xl font-black tracking-widest hover:scale-105 active:scale-95 transition-all shadow-xl shadow-slate-900/20 flex items-center gap-2">
                NUEVO ÍTEM
            </button>
        </div>
    </header>

    <div class="flex-1 overflow-auto p-10">
        <div id="productos-grid" class="grid-dense">
            <!-- Cargando... -->
        </div>
    </div>
</main>

<!-- Modal Producto -->
<div id="modal-producto" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-2xl rounded-[2.5rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="px-10 py-8 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <h3 class="text-2xl font-black text-slate-800 tracking-tight" id="modal-title">Configurar Ítem</h3>
            <button onclick="cerrar()" class="text-slate-400 hover:text-slate-600 transition"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="form-producto" class="p-10">
            <input type="hidden" id="p_id">
            
            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Tipo de Ítem</label>
                    <select id="tipo" class="w-full h-12 px-4 bg-slate-50 border border-slate-200 rounded-2xl font-bold">
                        <option value="producto">📦 Producto (Inventariable)</option>
                        <option value="servicio">🛠️ Servicio (No Inventariable)</option>
                        <option value="combo">🎁 Combo / Kit</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Código SKU / EAN</label>
                    <input type="text" id="codigo" required placeholder="SKU-001"
                           class="w-full h-12 px-4 bg-slate-50 border border-slate-200 rounded-2xl font-black tracking-widest uppercase">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Nombre del Producto o Servicio</label>
                <input type="text" id="nombre" required placeholder="Ej: Acer Aspire 5 A515-56-32DK"
                       class="w-full h-12 px-4 bg-slate-50 border border-slate-200 rounded-2xl font-bold text-slate-700">
            </div>

            <div class="grid grid-cols-3 gap-6 mb-8">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Precio de Venta</label>
                    <input type="number" id="precio_venta" step="0.01" required placeholder="0.00"
                           class="w-full h-12 px-4 bg-slate-50 border border-slate-200 rounded-2xl font-black text-honduras text-xl tabular-nums">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Tasa ISV</label>
                    <select id="tasa_isv" class="w-full h-12 px-4 bg-slate-50 border border-slate-200 rounded-2xl font-black text-center">
                        <option value="15.00">15% General</option>
                        <option value="18.00">18% Especial</option>
                        <option value="0.00">Exento</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Maneja Lotes</label>
                    <div class="flex items-center gap-3 pt-2">
                        <input type="checkbox" id="maneja_lotes" class="w-6 h-6 rounded-lg text-honduras border-slate-300">
                        <span class="text-xs font-bold text-slate-500 uppercase">Trazabilidad</span>
                    </div>
                </div>
            </div>

            <div class="pt-6 border-t border-slate-100 flex gap-4">
                <button type="button" onclick="cerrar()" class="flex-1 h-14 bg-slate-100 text-slate-500 font-black rounded-3xl hover:bg-slate-200 transition">CANCELAR</button>
                <button type="submit" class="flex-1 h-14 bg-dark text-white font-black rounded-3xl hover:shadow-2xl transition-all tracking-[0.2em]">GUARDAR ÍTEM</button>
            </div>
        </form>
    </div>
</div>

<script>
let todosLosProductos = [];
document.addEventListener('DOMContentLoaded', cargar);

async function cargar() {
    const res = await fetch('api/com-productos.php');
    const json = await res.json();
    todosLosProductos = json.data || [];
    render(todosLosProductos);
}

function render(data) {
    const grid = document.getElementById('productos-grid');
    if (data.length === 0) {
        grid.innerHTML = '<div class="col-span-full py-20 text-center opacity-30 font-black uppercase text-xl tracking-widest italic">Cero resultados comerciales...</div>';
        return;
    }
    grid.innerHTML = data.map(p => `
        <div class="product-card bg-white rounded-[2rem] p-7 border border-slate-200 flex flex-col group cursor-pointer" onclick='abrirEditar(${JSON.stringify(p)})'>
            <div class="flex items-start justify-between mb-4">
                <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-400 group-hover:text-honduras transition-colors">
                    ${p.tipo === 'servicio' ? '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>' : '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>'}
                </div>
                <span class="text-[9px] font-black uppercase text-slate-400 border border-slate-100 px-3 py-1 rounded-full group-hover:border-honduras transition-colors">${p.codigo}</span>
            </div>
            <h4 class="font-black text-slate-800 text-base leading-tight mb-2 uppercase tracking-tighter">${p.nombre}</h4>
            <div class="mt-auto pt-6 flex items-end justify-between border-t border-slate-50 italic">
                <div>
                   <span class="block text-[8px] font-black text-slate-400 uppercase tracking-widest">Precio Venta</span>
                   <span class="text-xl font-black text-slate-900 tabular-nums">${fmt(p.precio_venta)}</span>
                </div>
                <div class="text-right">
                    <span class="inline-block px-3 py-1 bg-blue-50 text-honduras text-[10px] font-black rounded-lg">${p.tasa_isv}% ISV</span>
                </div>
            </div>
        </div>
    `).join('');
}

function filtrar() {
    const q = document.getElementById('buscador').value.toLowerCase();
    const filtered = todosLosProductos.filter(p => p.nombre.toLowerCase().includes(q) || p.codigo.toLowerCase().includes(q));
    render(filtered);
}

function fmt(n) { return new Intl.NumberFormat('es-HN', { style:'currency', currency:'HNL' }).format(n); }

const modal = document.getElementById('modal-producto');
function nuevoProducto() {
    document.getElementById('form-producto').reset();
    document.getElementById('p_id').value = '';
    document.getElementById('modal-title').textContent = 'Crear Nuevo Ítem';
    modal.classList.remove('hidden');
}

function abrirEditar(p) {
    document.getElementById('p_id').value = p.id;
    document.getElementById('codigo').value = p.codigo;
    document.getElementById('nombre').value = p.nombre;
    document.getElementById('precio_venta').value = p.precio_venta;
    document.getElementById('tasa_isv').value = parseFloat(p.tasa_isv).toFixed(2);
    document.getElementById('tipo').value = p.tipo;
    document.getElementById('maneja_lotes').checked = p.maneja_lotes == 1;
    document.getElementById('modal-title').textContent = 'Editar Ítem';
    modal.classList.remove('hidden');
}

function cerrar() { modal.classList.add('hidden'); }

document.getElementById('form-producto').addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = {
        id: document.getElementById('p_id').value,
        codigo: document.getElementById('codigo').value,
        nombre: document.getElementById('nombre').value,
        precio_venta: document.getElementById('precio_venta').value,
        tasa_isv: document.getElementById('tasa_isv').value,
        tipo: document.getElementById('tipo').value,
        maneja_lotes: document.getElementById('maneja_lotes').checked ? 1 : 0,
        maneja_inventario: document.getElementById('tipo').value === 'producto' ? 1 : 0
    };

    const method = data.id ? 'PUT' : 'POST';
    const res = await fetch('api/com-productos.php', {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });

    if (res.ok) {
        Swal.fire({ icon: 'success', title: 'Excelente!', text: 'Ítem guardado con éxito.', background: '#fff', confirmButtonColor: '#0073cf' });
        cerrar();
        cargar();
    } else {
        Swal.fire({ icon: 'error', title: 'Oops...', text: 'Error al procesar la solicitud.' });
    }
});
</script>
</body>
</html>
