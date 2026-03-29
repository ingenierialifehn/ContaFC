<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();

$user    = Auth::user();
$empresa = null;
try {
    $db = Database::getInstance()->getPdo();
    $empresa = $db->query("SELECT * FROM empresas WHERE id = " . Auth::empresaId())->fetch();
} catch (\Throwable $e) {}

$activeNav = 'tesoreria';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50 text-slate-700">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tesorería: Bancos – <?= htmlspecialchars($empresa['nombre'] ?? 'ContaFC') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT:'#1e3a5f', light:'#2563eb', dark:'#0f1f3d' },
                        honduras: '#0073cf',
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
            <div class="w-14 h-14 bg-sky-50 text-honduras rounded-3xl flex items-center justify-center shadow-inner group">
                 <svg class="w-8 h-8 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <div>
                <nav class="flex text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 gap-2">
                    <span class="text-honduras">Gestión de Tesorería</span>
                    <span>/</span>
                    <span>Control Bancario</span>
                </nav>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight leading-none">Cuentas de Banco</h1>
                <p class="text-slate-500 text-xs mt-1">Vinculación de cuentas bancarias y monitoreo de saldos.</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="abrirModalBanco()" 
                    class="bg-honduras px-6 py-3 text-white rounded-2xl hover:opacity-90 transition font-bold shadow-lg shadow-blue-500/20 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nueva Cuenta
            </button>
        </div>
    </header>

    <div class="flex-1 overflow-auto p-8">
        <div class="max-w-7xl mx-auto flex flex-col gap-8">
            <!-- Bancos Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="bancos-grid">
                <!-- Se cargará via JS -->
                 <div class="col-span-full py-20 text-center text-slate-400 italic">Cargando cuentas bancarias...</div>
            </div>

            <!-- Resumen de Recurrencia -->
            <div class="bg-white rounded-[2.5rem] p-10 border border-slate-200 shadow-xl flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-black text-slate-800 tracking-tight">Comprobantes Recurrentes</h3>
                    <p class="text-sm text-slate-500 font-medium">Automatiza el registro de gastos fijos mensuales.</p>
                </div>
                <div class="flex gap-4">
                    <a href="<?= BASE_URL ?>/tesoreria_recurrentes.php" 
                       class="px-8 py-3 bg-slate-800 text-white rounded-2xl font-bold hover:bg-slate-900 transition flex items-center gap-2">
                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                         Ver Plantillas
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
let bancos = [];

document.addEventListener('DOMContentLoaded', cargarBancos);

async function cargarBancos() {
    const res = await fetch('<?= BASE_URL ?>/api/tesoreria.php?action=bancos');
    const json = await res.json();
    bancos = json.data || [];
    renderBancos();
}

function renderBancos() {
    const grid = document.getElementById('bancos-grid');
    if (!bancos.length) {
        grid.innerHTML = '<div class="col-span-full py-20 bg-white rounded-[2rem] border border-dashed border-slate-300 text-center text-slate-400 font-bold uppercase tracking-widest italic">No hay cuentas bancarias configuradas.</div>';
        return;
    }

    grid.innerHTML = bancos.map(b => `
        <div class="bg-white rounded-[2rem] border border-slate-200 p-8 shadow-sm hover:shadow-xl transition-all group overflow-hidden relative">
            <div class="absolute -right-10 -bottom-10 opacity-[0.03] group-hover:scale-125 transition-transform duration-700 pointer-events-none">
                 <svg class="w-48 h-48" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L1 21h22L12 2zm0 3.99L18.53 17H5.47L12 5.99zM11 14v2h2v-2h-2zm0-6v4h2V8h-2z"/></svg>
            </div>
            <div class="flex items-start justify-between mb-6">
                <div class="w-12 h-12 bg-sky-50 text-honduras rounded-2xl flex items-center justify-center font-black">
                    ${b.moneda}
                </div>
                <div class="flex gap-1">
                    <button onclick="conciliar(${b.id})" class="text-[10px] font-black tracking-widest bg-emerald-50 text-emerald-600 px-3 py-1.5 rounded-lg border border-emerald-100 hover:bg-emerald-600 hover:text-white transition">CONCILIAR</button>
                </div>
            </div>
            <h4 class="text-xl font-black text-slate-800 tracking-tight leading-tight">${b.nombre}</h4>
            <p class="font-mono text-xs text-slate-400 mt-1 uppercase font-bold tracking-widest">${b.numero_cuenta}</p>
            <div class="mt-6 pt-6 border-t border-slate-50 flex flex-col gap-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Cuenta Contable (Honduras)</span>
                <span class="text-sm font-bold text-slate-600 font-mono italic underline decoration-sky-300 decoration-2">${b.cta_cod} – ${b.cta_nom}</span>
            </div>
        </div>
    `).join('');
}

function abrirModalBanco() {
    Swal.fire({
        title: 'Vincular Cuenta Bancaria',
        width: '500px',
        html: `
            <div class="text-left space-y-4 p-4">
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Nombre / Alias (Banco Atlántida, etc)</label>
                    <input id="sw_nombre" class="w-full h-11 border border-slate-200 rounded-xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 text-sm font-bold">
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Número de Cuenta</label>
                    <input id="sw_numero" class="w-full h-11 border border-slate-200 rounded-xl px-4 outline-none font-mono text-sm font-bold">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Moneda</label>
                        <select id="sw_moneda" class="w-full h-11 border border-slate-200 rounded-xl px-4 outline-none text-xs font-black">
                            <option value="HNL">Lempiras (HNL)</option>
                            <option value="USD">Dólares (USD)</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Saldo Inicial</label>
                        <input id="sw_saldo" type="number" step="0.01" value="0.00" class="w-full h-11 border border-slate-200 rounded-xl px-4 outline-none font-mono text-sm text-right font-black">
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Cuenta PUC Asociada (1104...)</label>
                    <input id="sw_cta_search" placeholder="Buscar por código..." class="w-full h-11 border border-slate-200 rounded-xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 text-xs font-mono">
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Registrar Cuenta',
        customClass: { confirmButton: 'bg-honduras text-white px-8 py-3 rounded-2xl font-bold ml-2 shadow-lg', cancelButton: 'bg-slate-100 text-slate-500 px-8 py-3 rounded-2xl font-bold' },
        buttonsStyling: false,
        preConfirm: async () => {
             const cod = document.getElementById('sw_cta_search').value;
             // Buscar ID de cuenta contable
             const r = await fetch(`<?= BASE_URL ?>/api/cuentas.php?q=${cod}`);
             const j = await r.json();
             const cta = j.data?.[0];
             if(!cta) { Swal.showValidationMessage('La cuenta contable no existe.'); return false; }
             
             return {
                 action: 'add_banco',
                 nombre: document.getElementById('sw_nombre').value,
                 numero_cuenta: document.getElementById('sw_numero').value,
                 moneda: document.getElementById('sw_moneda').value,
                 saldo_inicial: document.getElementById('sw_saldo').value,
                 cuenta_id: cta.id
             };
        }
    }).then(async result => {
        if (result.isConfirmed) {
            const res = await fetch('<?= BASE_URL ?>/api/tesoreria.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(result.value)
            });
            if (res.ok) {
                Swal.fire({ icon:'success', title:'Vínculo exitoso', timer:1500, showConfirmButton:false });
                cargarBancos();
            }
        }
    });
}

function conciliar(id) {
    // Redirigir a módulo de conciliación (será creado en el siguiente paso de la misma tarea)
    Swal.fire({ icon:'info', title:'Conciliación Bancaria', text:'Procesando apertura de conciliación...', timer:1200, showConfirmButton:false });
    // window.location.href = `tesoreria_conciliacion.php?banco_id=${id}`;
}
</script>
</body>
</html>
