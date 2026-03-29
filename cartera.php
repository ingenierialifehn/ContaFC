<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requirePermiso('cartera');

$user    = Auth::user();
$empresa = null;
try {
    $db      = Database::getInstance()->getPdo();
    $empresa = $db->query("SELECT * FROM empresas WHERE id = " . Auth::empresaId())->fetch();

    // Listado de terceros para los selects
    $terceros = $db->query(
        "SELECT id, nombre FROM terceros WHERE empresa_id = " . Auth::empresaId() . " ORDER BY nombre"
    )->fetchAll();
} catch (\Throwable $e) {
    $terceros = [];
}

$activeNav = 'cartera';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-[#020617] text-slate-300">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ContaFC – Cartera y Recaudos | <?= htmlspecialchars($empresa['nombre'] ?? '') ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z'/></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>tailwind.config={theme:{extend:{colors:{brand:'#0ea5e9'},fontFamily:{sans:['Inter','system-ui','sans-serif']}}}}</script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { background: radial-gradient(circle at 20% 20%, rgba(14,165,233,.04) 0%, transparent 50%), #020617; }
        .glass { background:rgba(15,23,42,.5); backdrop-filter:blur(20px); border:1px solid rgba(255,255,255,.04); }
        .no-scrollbar::-webkit-scrollbar{display:none} .no-scrollbar{-ms-overflow-style:none;scrollbar-width:none}
        .badge-pendiente { background:rgba(245,158,11,.1); color:#f59e0b; }
        .badge-mora      { background:rgba(244,63,94,.1);  color:#f43f5e; }
        .badge-parcial   { background:rgba(99,102,241,.1); color:#818cf8; }
        .badge-pagado    { background:rgba(16,185,129,.1); color:#10b981; }
    </style>
</head>
<body class="h-full flex overflow-hidden">

<?php require __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-1 flex flex-col min-w-0 h-full overflow-hidden">

    <!-- Header -->
    <header class="px-10 py-8 border-b border-white/[0.04] flex items-center justify-between bg-[#020617]/80 backdrop-blur-sm z-10">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_#10b981]"></div>
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-[0.4em]">Módulo Financiero / Cartera</span>
            </div>
            <h1 class="text-3xl font-black text-white tracking-tighter italic uppercase">Cartera &amp; <span class="text-brand">Recaudos</span></h1>
            <p class="text-slate-500 text-xs mt-2 uppercase tracking-widest font-bold">Amortización · Cuotas · Estado de Cuenta · Recibos</p>
        </div>
        <div class="flex items-center gap-4">
            <button onclick="openTab('tab-recaudos'); openRecaudoModal()" class="h-11 px-6 bg-emerald-500/10 hover:bg-emerald-500 border border-emerald-500/20 text-emerald-500 hover:text-white rounded-2xl text-[10px] font-black uppercase tracking-widest transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Registrar Recaudo
            </button>
            <button onclick="openCreditoModal()" class="h-11 px-6 bg-brand/10 hover:bg-brand border border-brand/20 text-brand hover:text-dark rounded-2xl text-[10px] font-black uppercase tracking-widest transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nuevo Crédito
            </button>
        </div>
    </header>

    <!-- Tabs -->
    <div class="px-10 border-b border-white/[0.04] flex items-center gap-6">
        <?php foreach(['creditos'=>'Créditos Activos','tab-cuotas'=>'Plan de Pagos','tab-recaudos'=>'Historial Recaudos'] as $tid => $tlabel): ?>
        <button onclick="openTab('<?= $tid ?>')" id="btn-<?= $tid ?>"
                class="tab-btn py-5 text-[10px] font-black uppercase tracking-widest border-b-2 transition-all <?= $tid === 'creditos' ? 'border-brand text-brand' : 'border-transparent text-slate-600 hover:text-white' ?>">
            <?= $tlabel ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Tab Panels -->
    <div class="flex-1 overflow-y-auto no-scrollbar px-10 py-8">

        <!-- TAB: Créditos -->
        <div id="tab-creditos" class="tab-panel">
            <div class="glass rounded-[2.5rem] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[9px] font-black text-slate-600 uppercase tracking-[0.3em] border-b border-white/[0.04]">
                                <th class="px-8 py-6">Ref / Documento</th>
                                <th class="px-8 py-6">Cliente / Deudor</th>
                                <th class="px-8 py-6 text-right">Valor Total</th>
                                <th class="px-8 py-6 text-right">Saldo Actual</th>
                                <th class="px-8 py-6 text-center">Cuotas</th>
                                <th class="px-8 py-6 text-center">Frecuencia</th>
                                <th class="px-8 py-6 text-center">Estado</th>
                                <th class="px-8 py-6 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-creditos" class="divide-y divide-white/[0.02]">
                            <tr><td colspan="8" class="px-8 py-16 text-center text-slate-600 text-[11px] uppercase font-bold tracking-widest italic">Cargando créditos...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: Plan de Pagos -->
        <div id="tab-tab-cuotas" class="tab-panel hidden">
            <div class="mb-6 flex items-center gap-4">
                <label class="text-[10px] font-bold text-slate-500 uppercase">Seleccionar Crédito</label>
                <select id="sel-credito-cuotas" onchange="loadCuotas(this.value)" class="bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-sm text-white font-bold focus:ring-2 focus:ring-brand outline-none">
                    <option value="">— Elija un crédito —</option>
                </select>
            </div>
            <div class="glass rounded-[2.5rem] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[9px] font-black text-slate-600 uppercase tracking-[0.3em] border-b border-white/[0.04]">
                                <th class="px-8 py-6">Cuota #</th>
                                <th class="px-8 py-6">Vencimiento</th>
                                <th class="px-8 py-6 text-right">Capital</th>
                                <th class="px-8 py-6 text-right">Interés</th>
                                <th class="px-8 py-6 text-right">Total Cuota</th>
                                <th class="px-8 py-6 text-right">Pagado</th>
                                <th class="px-8 py-6 text-right">Saldo</th>
                                <th class="px-8 py-6 text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-cuotas" class="divide-y divide-white/[0.02]">
                            <tr><td colspan="8" class="px-8 py-16 text-center text-slate-600 text-[11px] uppercase font-bold tracking-widest italic">Seleccione un crédito para ver el plan de pagos</td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="cuotas-summary" class="px-8 py-6 border-t border-white/[0.04] hidden">
                    <div class="grid grid-cols-3 gap-6">
                        <div class="text-center"><div class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Total Capital</div><div class="font-black text-white" id="sum-capital">—</div></div>
                        <div class="text-center"><div class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Total Interés</div><div class="font-black text-brand" id="sum-interes">—</div></div>
                        <div class="text-center"><div class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Total a Pagar</div><div class="font-black text-emerald-400" id="sum-total">—</div></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: Recaudos -->
        <div id="tab-tab-recaudos" class="tab-panel hidden">
            <div class="glass rounded-[2.5rem] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[9px] font-black text-slate-600 uppercase tracking-[0.3em] border-b border-white/[0.04]">
                                <th class="px-8 py-6">Fecha</th>
                                <th class="px-8 py-6">Cliente</th>
                                <th class="px-8 py-6">Glosa / Concepto</th>
                                <th class="px-8 py-6 text-center">Método</th>
                                <th class="px-8 py-6 text-right">Valor Recaudado</th>
                                <th class="px-8 py-6 text-center">Recibo</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-recaudos" class="divide-y divide-white/[0.02]">
                            <tr><td colspan="6" class="px-8 py-16 text-center text-slate-600 text-[11px] uppercase font-bold tracking-widest italic">Cargando recaudos...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</main>

<!-- MODAL: Nuevo Crédito ─────────────────────────────────────────────────── -->
<div id="modal-credito" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 backdrop-blur-sm p-6">
    <div class="glass rounded-[3rem] max-w-2xl w-full p-12 border border-white/10 shadow-2xl">
        <div class="flex items-center justify-between mb-10">
            <div>
                <h2 class="text-2xl font-black text-white italic uppercase tracking-tight">Registrar Nuevo Crédito</h2>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1">Amortización Francesa Automática</p>
            </div>
            <button onclick="closeModal('modal-credito')" class="w-10 h-10 rounded-2xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-slate-400 hover:text-white transition">✕</button>
        </div>
        <form id="form-credito" class="grid grid-cols-2 gap-6" onsubmit="submitCredito(event)">
            <div class="col-span-2">
                <label class="label-field">Cliente / Deudor</label>
                <select name="tercero_id" required class="input-field w-full">
                    <option value="">— Seleccionar —</option>
                    <?php foreach($terceros as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label-field">Referencia / Número Lote</label>
                <input name="referencia_doc" class="input-field w-full" placeholder="Ej: LOTE-A-05 / INV-2026-001">
            </div>
            <div>
                <label class="label-field">Valor Total del Crédito (L)</label>
                <input name="valor_total" type="number" step="0.01" min="1" required class="input-field w-full" placeholder="0.00">
            </div>
            <div>
                <label class="label-field">Tasa de Interés Anual (%)</label>
                <input name="tasa_interes" type="number" step="0.01" min="0" value="0" class="input-field w-full">
            </div>
            <div>
                <label class="label-field">Número de Cuotas</label>
                <input name="cuotas_totales" type="number" min="1" max="360" required class="input-field w-full" placeholder="12">
            </div>
            <div>
                <label class="label-field">Frecuencia de Pago</label>
                <select name="frecuencia" class="input-field w-full">
                    <option value="mensual">Mensual</option>
                    <option value="quincenal">Quincenal</option>
                    <option value="semanal">Semanal</option>
                </select>
            </div>
            <div>
                <label class="label-field">Fecha de Inicio</label>
                <input name="fecha_inicio" type="date" required class="input-field w-full" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-span-2">
                <label class="label-field">Descripción / Glosa</label>
                <textarea name="descripcion" rows="2" class="input-field w-full" placeholder="Ej: Venta Lote B-3, Residencial Las Palmas"></textarea>
            </div>
            <div class="col-span-2 flex justify-end gap-4 pt-4">
                <button type="button" onclick="closeModal('modal-credito')" class="h-12 px-8 bg-white/5 hover:bg-white/10 rounded-2xl text-[10px] font-black text-slate-400 uppercase tracking-widest transition">Cancelar</button>
                <button type="submit" class="h-12 px-10 bg-brand rounded-2xl text-dark font-black text-[10px] uppercase tracking-widest hover:scale-105 transition shadow-lg shadow-brand/20">Generar Plan de Amortización</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Registrar Recaudo ─────────────────────────────────────────────── -->
<div id="modal-recaudo" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 backdrop-blur-sm p-6">
    <div class="glass rounded-[3rem] max-w-lg w-full p-12 border border-white/10 shadow-2xl">
        <div class="flex items-center justify-between mb-10">
            <div>
                <h2 class="text-2xl font-black text-white italic uppercase tracking-tight">Registrar Recaudo</h2>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1">Aplicación automática a cuotas pendientes (FIFO)</p>
            </div>
            <button onclick="closeModal('modal-recaudo')" class="w-10 h-10 rounded-2xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-slate-400 hover:text-white transition">✕</button>
        </div>
        <form id="form-recaudo" class="space-y-6" onsubmit="submitRecaudo(event)">
            <div>
                <label class="label-field">Cliente</label>
                <select name="tercero_id" required class="input-field w-full">
                    <option value="">— Seleccionar —</option>
                    <?php foreach($terceros as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label-field">Crédito (opcional – para aplicar a cuotas)</label>
                <select name="credito_id" id="sel-recaudo-credito" class="input-field w-full">
                    <option value="">— Sin asignar —</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label-field">Fecha</label>
                    <input name="fecha" type="date" required class="input-field w-full" value="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <label class="label-field">Valor Recibido (L)</label>
                    <input name="valor_total" type="number" step="0.01" min="0.01" required class="input-field w-full" placeholder="0.00">
                </div>
            </div>
            <div>
                <label class="label-field">Método de Pago</label>
                <select name="metodo_pago" class="input-field w-full">
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia Bancaria</option>
                    <option value="cheque">Cheque</option>
                    <option value="tarjeta">Tarjeta</option>
                </select>
            </div>
            <div>
                <label class="label-field">Concepto / Referencia</label>
                <input name="glosa" class="input-field w-full" placeholder="Ej: Abono cuota 3/12 Lote A-5">
            </div>
            <div class="flex justify-end gap-4 pt-4">
                <button type="button" onclick="closeModal('modal-recaudo')" class="h-12 px-8 bg-white/5 hover:bg-white/10 rounded-2xl text-[10px] font-black text-slate-400 uppercase tracking-widest transition">Cancelar</button>
                <button type="submit" class="h-12 px-10 bg-emerald-500 rounded-2xl text-white font-black text-[10px] uppercase tracking-widest hover:scale-105 transition shadow-lg shadow-emerald-500/20">Registrar y Generar Recibo</button>
            </div>
        </form>
    </div>
</div>

<!-- PRINT: Recibo de Pago ─────────────────────────────────────────────────── -->
<div id="print-recibo" class="fixed inset-0 z-[100] hidden bg-white overflow-auto p-10 text-slate-900 font-sans">
    <div class="max-w-2xl mx-auto border border-slate-200 rounded-2xl overflow-hidden">
        <div class="bg-slate-900 text-white px-10 py-8 flex items-center justify-between">
            <div>
                <div class="text-2xl font-black tracking-tighter italic">Conta<span class="text-sky-400">FC</span></div>
                <div class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1"><?= htmlspecialchars($empresa['nombre'] ?? '') ?></div>
            </div>
            <div class="text-right">
                <div class="text-xs font-black text-slate-400 uppercase tracking-widest">Recibo de Pago</div>
                <div class="text-3xl font-black text-white tracking-tighter italic" id="recibo-num">#000000</div>
            </div>
        </div>
        <div class="p-10 space-y-6">
            <div class="grid grid-cols-2 gap-6 pb-6 border-b border-slate-100">
                <div><div class="text-[9px] font-black text-slate-400 uppercase mb-1">Cliente</div><div class="text-base font-black text-slate-800" id="recibo-cliente">—</div></div>
                <div><div class="text-[9px] font-black text-slate-400 uppercase mb-1">Fecha</div><div class="font-black text-slate-800" id="recibo-fecha">—</div></div>
                <div><div class="text-[9px] font-black text-slate-400 uppercase mb-1">Concepto</div><div class="font-bold text-slate-700" id="recibo-glosa">—</div></div>
                <div><div class="text-[9px] font-black text-slate-400 uppercase mb-1">Método de Pago</div><div class="font-black text-slate-800 uppercase" id="recibo-metodo">—</div></div>
            </div>
            <div class="py-8 text-center">
                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Recibido (HNL)</div>
                <div class="text-5xl font-black text-slate-900 tracking-tighter" id="recibo-valor">L. 0.00</div>
            </div>
            <div class="pt-6 border-t border-slate-100 flex justify-between items-center">
                <div class="text-[9px] text-slate-400 font-bold uppercase">Este recibo es comprobante de pago.</div>
                <button onclick="window.print(); closeRecibo()" class="px-6 py-2 bg-slate-900 text-white rounded-xl text-xs font-black uppercase tracking-widest">Imprimir</button>
            </div>
        </div>
    </div>
</div>

<style>
    .label-field { display:block; font-size:9px; font-weight:900; color:#64748b; 
                   text-transform:uppercase; letter-spacing:.2em; margin-bottom:.4rem; }
    .input-field  { background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.07); 
                   border-radius:.75rem; padding:.75rem 1rem; color:#e2e8f0; font-size:.8rem; 
                   font-weight:700; outline:none; transition:all .2s; }
    .input-field:focus { border-color:rgba(14,165,233,.5); box-shadow:0 0 0 3px rgba(14,165,233,.1); }
    .input-field option { background:#0f172a; color:#e2e8f0; }
    @media print { body > *:not(#print-recibo) { display:none !important; } #print-recibo { display:block !important; } }
</style>

<script>
const API = '<?= BASE_URL ?>/api/cartera.php';
const f   = new Intl.NumberFormat('es-HN', { style:'currency', currency:'HNL' });
let creditosCache = [];

// ─── Tabs ──────────────────────────────────────────────────────────────────
function openTab(id) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('border-brand','text-brand');
        b.classList.add('border-transparent','text-slate-600');
    });
    document.getElementById(`tab-${id}`).classList.remove('hidden');
    const btn = document.getElementById(`btn-${id}`);
    if (btn) { btn.classList.add('border-brand','text-brand'); btn.classList.remove('border-transparent','text-slate-600'); }
}

// ─── Load Data ─────────────────────────────────────────────────────────────
async function loadCreditos() {
    const st = document.getElementById('tbody-creditos');
    try {
        const r = await fetch(`${API}?action=creditos&estado=activo`);
        const { data } = await r.json();
        creditosCache = data || [];
        populateCreditoSelects();

        if (!data.length) {
            st.innerHTML = emptyRow(8, 'No hay créditos activos. Crea el primero haciendo clic en el botón superior.');
            return;
        }
        st.innerHTML = data.map(c => `
            <tr class="group hover:bg-white/[0.01] transition-all">
                <td class="px-8 py-6">
                    <a href="#" onclick="verEstadoCuenta(${c.id})" class="text-[11px] font-black text-brand hover:underline italic uppercase">${c.referencia_doc || `CR-${String(c.id).padStart(5,'0')}`}</a>
                </td>
                <td class="px-8 py-6 font-bold text-slate-200 text-sm">${c.tercero_nombre || '—'}</td>
                <td class="px-8 py-6 text-right font-black text-white tabular-nums">${f.format(c.valor_total)}</td>
                <td class="px-8 py-6 text-right font-black tabular-nums ${parseFloat(c.saldo_actual) > 0 ? 'text-rose-400' : 'text-emerald-400'}">${f.format(c.saldo_actual)}</td>
                <td class="px-8 py-6 text-center text-slate-400 font-bold">${c.cuotas_totales}</td>
                <td class="px-8 py-6 text-center"><span class="text-[9px] font-black uppercase text-slate-500 tracking-widest">${c.frecuencia}</span></td>
                <td class="px-8 py-6 text-center">
                    <span class="px-3 py-1 rounded-xl text-[9px] font-black uppercase tracking-widest ${c.estado === 'activo' ? 'bg-emerald-500/10 text-emerald-400' : c.estado === 'liquidado' ? 'bg-sky-500/10 text-sky-400' : 'bg-rose-500/10 text-rose-400'}">${c.estado}</span>
                </td>
                <td class="px-8 py-6 text-center">
                    <div class="flex gap-2 justify-center opacity-0 group-hover:opacity-100 transition">
                        <button onclick="verEstadoCuenta(${c.id})" class="w-8 h-8 rounded-xl bg-sky-500/10 hover:bg-sky-500 text-sky-500 hover:text-white flex items-center justify-center transition text-xs" title="Plan de Pagos">📋</button>
                        <button onclick="openRecaudoFor(${c.id})" class="w-8 h-8 rounded-xl bg-emerald-500/10 hover:bg-emerald-500 text-emerald-400 hover:text-white flex items-center justify-center transition text-xs" title="Registrar Pago">💰</button>
                        <button onclick="deleteCredito(${c.id})" class="w-8 h-8 rounded-xl bg-rose-500/10 hover:bg-rose-500 text-rose-400 hover:text-white flex items-center justify-center transition text-xs" title="Anular">✕</button>
                    </div>
                </td>
            </tr>
        `).join('');
    } catch(e) {
        st.innerHTML = emptyRow(8, 'Error al cargar créditos.');
    }
}

async function loadCuotas(creditoId) {
    if (!creditoId) return;
    const st = document.getElementById('tbody-cuotas');
    const sum = document.getElementById('cuotas-summary');
    st.innerHTML = emptyRow(8, 'Cargando...');
    try {
        const r = await fetch(`${API}?action=cuotas&credito_id=${creditoId}`);
        const { data } = await r.json();
        if (!data.length) { st.innerHTML = emptyRow(8, 'Sin cuotas generadas.'); return; }
        
        let totalCap = 0, totalInt = 0;
        st.innerHTML = data.map(q => {
            const total = parseFloat(q.valor_capital) + parseFloat(q.valor_interes);
            const saldo = total - parseFloat(q.valor_pagado);
            totalCap += parseFloat(q.valor_capital);
            totalInt += parseFloat(q.valor_interes);
            return `<tr class="group hover:bg-white/[0.01] transition-all ${new Date(q.fecha_vencimiento) < new Date() && q.estado === 'pendiente' ? 'bg-rose-500/5' : ''}">
                <td class="px-8 py-5 font-black text-white italic">#${q.num_cuota}</td>
                <td class="px-8 py-5 text-slate-400 font-bold">${q.fecha_vencimiento}</td>
                <td class="px-8 py-5 text-right font-mono text-slate-300">${f.format(q.valor_capital)}</td>
                <td class="px-8 py-5 text-right font-mono text-brand">${f.format(q.valor_interes)}</td>
                <td class="px-8 py-5 text-right font-black text-white">${f.format(total)}</td>
                <td class="px-8 py-5 text-right text-emerald-400 font-mono">${f.format(q.valor_pagado)}</td>
                <td class="px-8 py-5 text-right font-black ${saldo > 0 ? 'text-rose-400' : 'text-emerald-400'}">${f.format(saldo)}</td>
                <td class="px-8 py-5 text-center"><span class="px-3 py-1 rounded-xl text-[9px] font-black uppercase badge-${q.estado}">${q.estado}</span></td>
            </tr>`;
        }).join('');

        document.getElementById('sum-capital').textContent = f.format(totalCap);
        document.getElementById('sum-interes').textContent = f.format(totalInt);
        document.getElementById('sum-total').textContent   = f.format(totalCap + totalInt);
        sum.classList.remove('hidden');
    } catch(e) { st.innerHTML = emptyRow(8, 'Error al cargar cuotas.'); }
}

async function loadRecaudos() {
    const st = document.getElementById('tbody-recaudos');
    try {
        const r = await fetch(`${API}?action=recaudos`);
        const { data } = await r.json();
        if (!data || !data.length) { st.innerHTML = emptyRow(6, 'No hay recaudos registrados.'); return; }
        let numRecibo = 1;
        st.innerHTML = data.map(r => `
            <tr class="group hover:bg-white/[0.01] transition-all">
                <td class="px-8 py-5 text-slate-400 font-bold">${r.fecha}</td>
                <td class="px-8 py-5 font-black text-slate-200">${r.tercero_nombre || '—'}</td>
                <td class="px-8 py-5 text-slate-500 italic">${r.glosa || '—'}</td>
                <td class="px-8 py-5 text-center"><span class="text-[9px] font-black uppercase text-slate-500 bg-white/5 px-3 py-1 rounded-lg">${r.metodo_pago}</span></td>
                <td class="px-8 py-5 text-right font-black text-emerald-400 text-base tabular-nums">${f.format(r.valor_total)}</td>
                <td class="px-8 py-5 text-center">
                    <button onclick="printRecibo(${JSON.stringify(r).replace(/"/g, '&quot;')})" class="text-[9px] font-black text-brand hover:text-white transition uppercase tracking-widest opacity-0 group-hover:opacity-100">🖨 Imprimir</button>
                </td>
            </tr>
        `).join('');
    } catch(e) { st.innerHTML = emptyRow(6, 'Error al cargar recaudos.'); }
}

// ─── Helpers ──────────────────────────────────────────────────────────────
function emptyRow(cols, msg) {
    return `<tr><td colspan="${cols}" class="px-8 py-16 text-center text-slate-600 text-[11px] uppercase font-bold tracking-widest italic">${msg}</td></tr>`;
}

function populateCreditoSelects() {
    const selCuotas  = document.getElementById('sel-credito-cuotas');
    const selRecaudo = document.getElementById('sel-recaudo-credito');
    const opts = creditosCache.map(c =>
        `<option value="${c.id}">${c.referencia_doc || `CR-${String(c.id).padStart(5,'0')}`} — ${c.tercero_nombre}</option>`
    ).join('');
    selCuotas.innerHTML  = '<option value="">— Elija un crédito —</option>' + opts;
    selRecaudo.innerHTML = '<option value="">— Sin asignar —</option>' + opts;
}

function verEstadoCuenta(id) {
    document.getElementById('sel-credito-cuotas').value = id;
    openTab('tab-cuotas');
    loadCuotas(id);
}

// ─── Modals ───────────────────────────────────────────────────────────────
function openCreditoModal() {
    document.getElementById('form-credito').reset();
    document.getElementById('modal-credito').classList.replace('hidden','flex');
}
function openRecaudoModal() {
    document.getElementById('form-recaudo').reset();
    document.getElementById('modal-recaudo').classList.replace('hidden','flex');
}
function openRecaudoFor(creditoId) {
    openRecaudoModal();
    document.getElementById('sel-recaudo-credito').value = creditoId;
}
function closeModal(id) { document.getElementById(id).classList.replace('flex','hidden'); }

// ─── Submit Forms ─────────────────────────────────────────────────────────
async function submitCredito(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    Swal.showLoading();
    try {
        const r = await fetch(`${API}?action=credito`, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data) });
        const json = await r.json();
        if (json.success) {
            Swal.fire({ icon:'success', title:'Crédito Creado', text:'La tabla de amortización se generó automáticamente.' });
            closeModal('modal-credito');
            loadCreditos();
        } else throw new Error(json.error);
    } catch(err) { Swal.fire({ icon:'error', title:'Error', text:err.message }); }
}

async function submitRecaudo(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    Swal.showLoading();
    try {
        const r = await fetch(`${API}?action=recaudo`, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data) });
        const json = await r.json();
        if (json.success) {
            Swal.fire({ icon:'success', title:'Recaudo Aplicado', text:'El pago se aplicó a las cuotas pendientes.', timer:1500, showConfirmButton:false });
            closeModal('modal-recaudo');
            // Print receipt with form data
            printRecibo({ tercero_nombre: document.querySelector('#form-recaudo [name=tercero_id] option:checked')?.textContent, ...data, id: json.id });
            loadCreditos(); loadRecaudos();
        } else throw new Error(json.error);
    } catch(err) { Swal.fire({ icon:'error', title:'Error', text:err.message }); }
}

async function deleteCredito(id) {
    const { isConfirmed } = await Swal.fire({ title:'¿Anular crédito?', text:'Se marcará como anulado.', icon:'warning', showCancelButton:true, background:'#0f172a', color:'#fff', customClass:{ confirmButton:'bg-rose-600 px-6 py-2 rounded-xl text-xs font-bold ml-2', cancelButton:'bg-white/5 px-6 py-2 rounded-xl text-xs' }, buttonsStyling:false });
    if (!isConfirmed) return;
    const r = await fetch(API, { method:'DELETE', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ id }) });
    const json = await r.json();
    if (json.success) { loadCreditos(); Swal.fire({ icon:'success', title:'Anulado', timer:1200, showConfirmButton:false }); }
}

// ─── Recibo Imprimible ─────────────────────────────────────────────────────
function printRecibo(data) {
    document.getElementById('recibo-num').textContent  = `#${String(data.id || 1).padStart(6,'0')}`;
    document.getElementById('recibo-cliente').textContent = data.tercero_nombre || '—';
    document.getElementById('recibo-fecha').textContent   = data.fecha || new Date().toLocaleDateString('es-HN');
    document.getElementById('recibo-glosa').textContent   = data.glosa || 'Abono a crédito';
    document.getElementById('recibo-metodo').textContent  = data.metodo_pago || '—';
    document.getElementById('recibo-valor').textContent   = f.format(data.valor_total || 0);
    document.getElementById('print-recibo').classList.remove('hidden');
}
function closeRecibo() { document.getElementById('print-recibo').classList.add('hidden'); }

// ─── Init ──────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadCreditos();
    loadRecaudos();
    openTab('creditos');
});
</script>

</body>
</html>
