<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requirePermiso('comercial');

$db = Database::getInstance()->getPdo();
$eid = Auth::empresaId();

// Obtener CAI activo para esta empresa
$stmtC = $db->prepare("SELECT * FROM com_cai WHERE empresa_id = :eid AND activo = 1 AND fecha_limite >= CURDATE() AND consecutivo_actual < rango_hasta LIMIT 1");
$stmtC->execute([':eid' => $eid]);
$caiActivo = $stmtC->fetch();

$activeNav = 'nueva_factura'; 
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <title>ContaFC – Nueva Factura de Venta | Honduras</title>
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        .inp-line { border-bottom: 2px solid #e2e8f0; border-top: 0; border-left:0; border-right:0; background:transparent; outline:none !important; }
        .inp-line:focus { border-bottom-color: #0073cf; }
        .grid-compact tr:hover { background-color: rgba(0, 115, 207, 0.03); }
    </style>
</head>
<body class="h-full font-sans flex text-xs overflow-hidden">

<?php require __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-1 flex flex-col min-w-0 bg-white shadow-2xl overflow-hidden m-4 rounded-[2rem] border border-slate-200">
    <!-- Header SAR Compliant -->
    <header class="px-10 py-8 border-b border-slate-100 bg-slate-50/80 flex items-center justify-between">
        <div class="flex items-center gap-6">
            <div class="w-16 h-16 bg-honduras text-white rounded-3xl flex items-center justify-center shadow-xl shadow-blue-500/30">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight italic">Factura de <span class="text-honduras">Venta</span></h1>
                <?php if ($caiActivo): ?>
                <div class="flex items-center gap-3 mt-1">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest bg-white border px-2 py-0.5 rounded">CAI Autorizado</span>
                    <span class="font-mono text-slate-500 font-bold text-[10px]"><?= $caiActivo['cai'] ?></span>
                </div>
                <?php else: ?>
                <span class="text-rose-500 font-black text-[9px] uppercase animate-pulse">¡Error: Sin resolución CAI activa!</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="text-right">
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-1">Próximo Correlativo</div>
            <div class="text-3xl font-black text-slate-900 tracking-tighter tabular-nums" id="prox-numero">
               <?= $caiActivo ? sprintf("%s-%s-%s-%08d", $caiActivo['establecimiento'], $caiActivo['punto_emision'], $caiActivo['tipo_documento'], $caiActivo['consecutivo_actual']+1) : '000-000-00-00000000' ?>
            </div>
        </div>
    </header>

    <div class="flex-1 flex flex-col p-10 overflow-auto">
        <!-- Billing Info -->
        <div class="grid grid-cols-4 gap-8 mb-10">
            <div class="col-span-2">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Cliente (Razón Social o RTN)</label>
                <div class="relative">
                   <input type="text" id="busc-cliente" placeholder="Buscar cliente..." oninput="buscarTercero(this.value)"
                          class="w-full h-12 px-5 bg-slate-50 border border-slate-200 rounded-2xl font-bold text-slate-700 focus:ring-2 focus:ring-honduras transition-all">
                   <div id="res-busc-cliente" class="absolute left-0 right-0 top-14 bg-white shadow-2xl rounded-2xl border border-slate-100 z-20 hidden"></div>
                   <input type="hidden" id="cliente_id">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Fecha Emisión</label>
                <input type="date" id="fecha" value="<?= date('Y-m-d') ?>" 
                       class="w-full h-12 px-5 bg-slate-50 border border-slate-200 rounded-2xl font-bold text-slate-700">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Condición</label>
                <select id="tipo_pago" class="w-full h-12 px-5 bg-slate-50 border border-slate-200 rounded-2xl font-black text-honduras uppercase">
                    <option value="contado">CONTADO CASH</option>
                    <option value="credito">CRÉDITO COMERCIAL</option>
                </select>
            </div>
        </div>

        <!-- Detail Table -->
        <div class="flex-1 bg-white rounded-3xl border border-slate-200 overflow-hidden mb-8 shadow-sm">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                    <tr>
                        <th class="px-6 py-4 w-16">Item</th>
                        <th class="px-6 py-4">Descripción / Producto</th>
                        <th class="px-6 py-4 text-center w-24">Cant.</th>
                        <th class="px-6 py-4 text-right w-32">Precio Unit. (L.)</th>
                        <th class="px-6 py-4 text-center w-20">ISV %</th>
                        <th class="px-6 py-4 text-right w-32">Subtotal</th>
                        <th class="px-6 py-4 text-center w-12"></th>
                    </tr>
                </thead>
                <tbody id="lineas-factura" class="divide-y divide-slate-50">
                    <!-- Filas JS -->
                </tbody>
            </table>
            <div class="p-6 bg-slate-50/30">
                <button onclick="agregarFila()" class="px-6 py-3 bg-white border border-slate-200 rounded-2xl font-black text-slate-500 hover:text-honduras hover:border-honduras transition-all uppercase tracking-widest text-[10px] flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Añadir Ítem
                </button>
            </div>
        </div>

        <!-- Totals & Actions -->
        <div class="flex gap-10">
            <div class="flex-1">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Observaciones</label>
                <textarea id="observaciones" rows="5" placeholder="Términos de la garantía, transportistas, etc..." 
                          class="w-full p-6 bg-slate-50 border border-slate-200 rounded-3xl font-medium text-slate-700 outline-none focus:ring-2 focus:ring-honduras transition-all"></textarea>
            </div>
            <div class="w-80 bg-slate-900 text-white rounded-[2.5rem] p-8 shadow-2xl">
                <div class="space-y-4">
                    <div class="flex justify-between items-center opacity-60">
                        <span class="text-[10px] font-black uppercase tracking-widest">Subtotal Gravado</span>
                        <span class="font-mono text-sm" id="res-subtotal">L. 0.00</span>
                    </div>
                    <div class="flex justify-between items-center opacity-70">
                        <span class="text-[10px] font-black uppercase tracking-widest">ISV 15%</span>
                        <span class="font-mono text-sm" id="res-isv15">L. 0.00</span>
                    </div>
                     <div class="flex justify-between items-center opacity-70">
                        <span class="text-[10px] font-black uppercase tracking-widest">ISV 18%</span>
                        <span class="font-mono text-sm" id="res-isv18">L. 0.00</span>
                    </div>
                    <div class="pt-4 border-t border-white/10 flex justify-between items-end">
                        <span class="text-[10px] font-black uppercase tracking-widest text-honduras">Total Factura</span>
                        <span class="text-3xl font-black tabular-nums tracking-tighter" id="res-total">L. 0.00</span>
                    </div>
                </div>
                <button onclick="guardarFactura()" class="w-full mt-8 py-4 bg-honduras text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-blue-500/20 hover:scale-[1.02] active:scale-95 transition-all">
                    EMITIR FACTURA (SAR)
                </button>
            </div>
        </div>
    </div>
</main>

<script>
let lineas = [];

function agregarFila() {
    lineas.push({ p_id: 0, nombre: '', cant: 1, precio: 0, isv: 15, subtotal: 0, total_isv: 0 });
    renderGrid();
}

function renderGrid() {
    const body = document.getElementById('lineas-factura');
    body.innerHTML = lineas.map((l, i) => `
        <tr class="group">
            <td class="px-6 py-4 text-slate-400 font-bold font-mono text-center">${i+1}</td>
            <td class="px-6 py-4">
                <div class="relative">
                    <input type="text" value="${l.nombre}" placeholder="Escanee o busque ítem..." oninput="buscarProd(${i}, this.value)"
                           class="w-full h-10 px-4 bg-white border border-slate-100 rounded-xl font-bold focus:ring-2 focus:ring-honduras transition-all">
                    <div id="res-prod-${i}" class="absolute left-0 right-0 top-11 bg-white shadow-xl z-20 hidden border rounded-xl overflow-hidden"></div>
                </div>
            </td>
            <td class="px-6 py-4">
                <input type="number" value="${l.cant}" onchange="cambioCant(${i}, this.value)"
                       class="w-full h-10 text-center font-black bg-white border border-slate-100 rounded-xl">
            </td>
            <td class="px-6 py-4">
                <input type="number" step="0.01" value="${l.precio}" onchange="cambioPrecio(${i}, this.value)"
                       class="w-full h-10 text-right font-mono font-bold bg-white border border-slate-100 rounded-xl">
            </td>
            <td class="px-6 py-4 text-center">
                <span class="bg-blue-50 text-honduras px-2 py-1 rounded-lg font-black text-[9px]">${l.isv}%</span>
            </td>
            <td class="px-6 py-4 text-right">
                <span class="font-mono font-black text-slate-900 text-sm">${fmt(l.subtotal + l.total_isv)}</span>
            </td>
            <td class="px-6 py-4 text-center">
                <button onclick="borrarLinea(${i})" class="text-slate-300 hover:text-rose-500 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
            </td>
        </tr>
    `).join('');
    actualizarTotales();
}

// ─── Funciones Core Facturación ───────────────────────────────────────────
function cambioCant(i, v) { lineas[i].cant = parseFloat(v)||0; calcLinea(i); }
function cambioPrecio(i, v) { lineas[i].precio = parseFloat(v)||0; calcLinea(i); }

function calcLinea(i) {
    const l = lineas[i];
    l.subtotal = l.cant * l.precio;
    l.total_isv = l.subtotal * (l.isv / 100);
    renderGrid();
}

function actualizarTotales() {
    let sub = 0, isv15 = 0, isv18 = 0;
    lineas.forEach(l => {
        sub += l.subtotal;
        if (l.isv == 15) isv15 += l.total_isv;
        if (l.isv == 18) isv18 += l.total_isv;
    });
    document.getElementById('res-subtotal').textContent = fmt(sub);
    document.getElementById('res-isv15').textContent = fmt(isv15);
    document.getElementById('res-isv18').textContent = fmt(isv18);
    document.getElementById('res-total').textContent = fmt(sub + isv15 + isv18);
}

// ─── Buscadores con UX Premium ────────────────────────────────────────────
async function buscarTercero(q) {
    if (q.length < 2) { document.getElementById('res-busc-cliente').classList.add('hidden'); return; }
    const res = await fetch(`api/terceros.php?q=${q}&tipo=cliente`);
    const json = await res.json();
    const caja = document.getElementById('res-busc-cliente');
    caja.innerHTML = json.data.map(t => `
        <div onclick="selecTercero(${t.id}, '${t.razon_social}')" class="px-6 py-4 hover:bg-slate-50 cursor-pointer flex justify-between items-center border-b border-slate-50">
            <div><span class="font-black text-slate-800">${t.razon_social}</span><br><span class="text-[9px] text-slate-400">RTN: ${t.nit_cc}</span></div>
            <span class="text-[9px] font-black text-honduras uppercase">Seleccionar</span>
        </div>
    `).join('');
    caja.classList.remove('hidden');
}

function selecTercero(id, nom) {
    document.getElementById('cliente_id').value = id;
    document.getElementById('busc-cliente').value = nom;
    document.getElementById('res-busc-cliente').classList.add('hidden');
}

async function buscarProd(idx, q) {
    if (q.length < 2) { document.getElementById(`res-prod-${idx}`).classList.add('hidden'); return; }
    // En producción esto iría a una API de búsqueda filtrada
    const res = await fetch(`api/com-productos.php`);
    const json = await res.json();
    const data = (json.data || []).filter(p => p.nombre.toLowerCase().includes(q.toLowerCase()) || p.codigo.toLowerCase().includes(q.toLowerCase()));
    
    const caja = document.getElementById(`res-prod-${idx}`);
    caja.innerHTML = data.map(p => `
        <div onclick='selecProd(${idx}, ${JSON.stringify(p)})' class="px-4 py-3 hover:bg-blue-50 cursor-pointer text-xs border-b border-slate-50 flex justify-between">
            <span class="font-bold">${p.nombre}</span>
            <span class="font-bold text-honduras">${fmt(p.precio_venta)}</span>
        </div>
    `).join('');
    caja.classList.remove('hidden');
}

function selecProd(i, p) {
    lineas[i].p_id = p.id;
    lineas[i].nombre = p.nombre;
    lineas[i].precio = parseFloat(p.precio_venta);
    lineas[i].isv = parseFloat(p.tasa_isv);
    calcLinea(i);
}

function borrarLinea(i) { lineas.splice(i, 1); renderGrid(); }
function fmt(n) { return new Intl.NumberFormat('es-HN', { style:'currency', currency:'HNL' }).format(n); }

// ─── Guardado y conexión contable ─────────────────────────────────────────
async function guardarFactura() {
    const cid = document.getElementById('cliente_id').value;
    if (!cid || lineas.length === 0) { Swal.fire('Error', 'Datos incompletos', 'warning'); return; }

    const data = {
        cliente_id: cid,
        fecha: document.getElementById('fecha').value,
        tipo_pago: document.getElementById('tipo_pago').value,
        observaciones: document.getElementById('observaciones').value,
        lineas
    };

    Swal.fire({ title: 'Emitiendo Factura...', text: 'Registrando en SAR y generando asiento contable', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
        const res = await fetch('api/com-facturas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        if (json.success) {
            Swal.fire('Factura Emitida', `Número: ${json.numero}`, 'success').then(() => window.location.reload());
        } else {
            Swal.fire('Error', json.error || 'No se pudo emitir la factura', 'error');
        }
    } catch (e) {
        Swal.fire('Error', 'Problema de conexión con el servidor', 'error');
    }
}

// Iniciar con una fila
agregarFila();
</script>
</body>
</html>
