<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requireRol('admin');

$user    = Auth::user();
$empresa = null;
try {
    $db = Database::getInstance()->getPdo();
    $empresa = $db->query("SELECT * FROM empresas WHERE id = " . Auth::empresaId())->fetch();
} catch (\Throwable $e) {}

$activeNav = 'setup';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-[#020617] text-slate-300">
<head>
    <meta charset="UTF-8">
    <title>ContaFC – Mantenimiento & Reseteo</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23ef4444'><path d='M12 2L1 21h22L12 2zm0 3.99L18.53 17H5.47L12 5.99zM11 14v2h2v-2h-2zm0-6v4h2V8h-2z'/></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: '#ef4444',
                        dark: '#020617',
                        card: 'rgba(30, 41, 59, 0.5)',
                    },
                    fontFamily: { sans: ['Inter','system-ui','sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: radial-gradient(circle at 10% 20%, rgba(239, 68, 68, 0.05) 0%, transparent 40%), #020617; }
        .premium-card { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.03); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .premium-card:hover { border-color: rgba(239, 68, 68, 0.3); transform: translateY(-3px); }
        .dangerous-btn { background: linear-gradient(135deg, #ef4444 0%, #991b1b 100%); }
        .action-icon { @apply w-12 h-12 rounded-2xl flex items-center justify-center transition-colors; }
    </style>
</head>
<body class="h-full flex overflow-hidden">

<?php require __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-1 overflow-auto flex flex-col min-w-0">
    
    <header class="px-10 py-10 flex items-center justify-between border-b border-white/5">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <div class="w-1.5 h-1.5 rounded-full bg-rose-500 shadow-[0_0_8px_#ef4444]"></div>
                <span class="text-[10px] font-black text-rose-500 uppercase tracking-[0.4em]">Operations Center / Maintenance</span>
            </div>
            <h1 class="text-4xl font-black text-white tracking-tighter italic uppercase leading-none">
                Mantenimiento de <span class="text-brand">Datos</span>
            </h1>
            <p class="text-slate-400 text-xs mt-3 font-medium uppercase tracking-widest">Procedimientos de Alto Riesgo - Administrador</p>
        </div>
        
        <div class="flex items-center gap-4">
            <div class="flex flex-col items-end px-6 border-r border-white/5">
                <span class="text-[9px] font-black text-slate-500 uppercase tracking-[0.2em] mb-1">Empresa Destino</span>
                <span class="text-sm font-black text-white truncate max-w-[200px]"><?= htmlspecialchars($empresa['nombre'] ?? 'Honduras Core') ?></span>
            </div>
        </div>
    </header>

    <div class="p-10 space-y-12">
        
        <!-- Emergency Alert -->
        <div class="p-8 bg-rose-500/10 border border-rose-500/20 rounded-[2.5rem] flex gap-6 items-center">
            <div class="w-16 h-16 bg-rose-500/20 text-rose-500 rounded-3xl flex items-center justify-center shrink-0">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                <h4 class="text-lg font-black text-rose-500 italic uppercase">Advertencia de Seguridad Crítica</h4>
                <p class="text-sm text-slate-400 mt-1 leading-relaxed">Estas operaciones eliminan información de forma permanente. No hay opción de deshacer. <span class="text-white font-bold">Por favor, realice un backup antes de proceder.</span></p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            
            <!-- Card 1: Reset Parcial -->
            <div class="premium-card p-10 rounded-[3rem] flex flex-col group">
                <div class="w-14 h-14 bg-amber-500/10 text-amber-500 rounded-2xl flex items-center justify-center mb-8 border border-amber-500/20 group-hover:bg-amber-500/20 transition">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </div>
                <h3 class="text-xl font-black text-white italic tracking-tight uppercase">Borrar Transacciones</h3>
                <p class="text-xs text-slate-500 mt-3 leading-relaxed">Elimina todos los asientos, comprobantes, ventas y movimientos bancarios. Mantiene catálogos intactos (Cuentas, Clientes, Productos).</p>
                <div class="mt-auto pt-10">
                    <button onclick="ejecutarAccion('reset_accounting')" class="w-full py-4 bg-white/5 hover:bg-white/10 border border-white/5 rounded-2xl text-[10px] font-black text-white uppercase tracking-widest transition">Borrar Contabilidad</button>
                </div>
            </div>

            <!-- Card 2: Reset Total -->
            <div class="premium-card p-10 rounded-[3rem] flex flex-col group bg-gradient-to-br from-rose-500/5 to-transparent">
                <div class="w-14 h-14 bg-rose-500/10 text-rose-500 rounded-2xl flex items-center justify-center mb-8 border border-rose-500/20 group-hover:bg-rose-500/20 transition">
                   <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </div>
                <h3 class="text-xl font-black text-white italic tracking-tight uppercase">Borrón y Cuenta Nueva</h3>
                <p class="text-xs text-slate-500 mt-3 leading-relaxed">Elimina <span class="text-rose-400 font-bold">TODO</span>: Cuentas, Terceros, Productos, Asientos y Configuración. Especial si desea importar desde GDB o SQL.</p>
                <div class="mt-auto pt-10">
                    <button onclick="ejecutarAccion('reset_all')" class="w-full py-4 bg-rose-500/20 hover:bg-rose-500 text-rose-500 hover:text-white border border-rose-500/20 rounded-2xl text-[10px] font-black uppercase tracking-widest transition shadow-lg shadow-rose-500/5">Reiniciar Sistema (0%)</button>
                </div>
            </div>

            <!-- Card 3: Import SQL -->
            <div class="premium-card p-10 rounded-[3rem] flex flex-col group">
                <div class="w-14 h-14 bg-sky-500/10 text-sky-500 rounded-2xl flex items-center justify-center mb-8 border border-sky-500/20 group-hover:bg-sky-500/20 transition">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                </div>
                <h3 class="text-xl font-black text-white italic tracking-tight uppercase">Importar Archivo SQL</h3>
                <p class="text-xs text-slate-500 mt-3 leading-relaxed">Cargue un volcado de base de datos MySQL (.sql). Desactivamos las Foreign Keys para asegurar una compatibilidad total.</p>
                <div class="mt-auto pt-10">
                    <input type="file" id="sql-file" class="hidden" accept=".sql" onchange="uploadSQL(this)">
                    <button onclick="document.getElementById('sql-file').click()" class="w-full py-4 bg-sky-500/10 hover:bg-sky-500 text-sky-500 hover:text-white border border-sky-500/20 rounded-2xl text-[10px] font-black uppercase tracking-widest transition">Cargar .SQL</button>
                </div>
            </div>

        </div>

        <!-- Foot Links -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
             <div onclick="location.href='migracion.php'" class="premium-card p-8 rounded-[2rem] flex items-center justify-between cursor-pointer group">
                  <div class="flex items-center gap-4">
                       <div class="w-12 h-12 bg-firebird bg-opacity-10 text-firebird rounded-xl flex items-center justify-center">
                           <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                       </div>
                       <div>
                           <p class="text-sm font-black text-white italic uppercase">Procesar Archivo Firebird GDB</p>
                           <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Sincronizador WXManager Legacy</p>
                       </div>
                  </div>
                  <svg class="w-5 h-5 text-slate-600 group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7-7 7"/></svg>
             </div>

             <div onclick="location.href='backups.php'" class="premium-card p-8 rounded-[2rem] flex items-center justify-between cursor-pointer group">
                  <div class="flex items-center gap-4">
                       <div class="w-12 h-12 bg-emerald-500/10 text-emerald-500 rounded-xl flex items-center justify-center">
                           <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16v2a2 2 0 01-2 2H5a2 2 0 01-2-2v-7a2 2 0 012-2h2m3-4H9a2 2 0 00-2 2v7a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-1M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                       </div>
                       <div>
                           <p class="text-sm font-black text-white italic uppercase">Historial de Backups SQL</p>
                           <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Descarga y Gestión de Respaldos</p>
                       </div>
                  </div>
                  <svg class="w-5 h-5 text-slate-600 group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7-7 7"/></svg>
             </div>
        </div>

    </div>

</main>

<script>
async function ejecutarAccion(action) {
    const texts = {
        'reset_all': { title: '¿RESET TOTAL?', text: 'Se borrará absolutamente TODO (Cuentas, Terceros, Ventas, etc). El sistema quedará vacío.' },
        'reset_accounting': { title: '¿Borrar Contabilidad?', text: 'Se borrarán Asientos y Comprobantes pero conservamos tu Plan de Cuentas y Terceros.' }
    };

    const confirm = await Swal.fire({
        title: texts[action].title,
        text: texts[action].text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, PROCEDER (ID: ADN)',
        cancelButtonText: 'Cancelar',
        background: '#0f172a',
        color: '#fff',
        customClass: { confirmButton: 'bg-rose-600 px-6 py-2 rounded-xl text-xs font-bold ml-2', cancelButton: 'bg-white/5 px-6 py-2 rounded-xl text-xs font-bold' },
        buttonsStyling: false
    });

    if (confirm.isConfirmed) {
        Swal.showLoading();
        const fd = new FormData(); fd.append('action', action);
        try {
            const res = await fetch('api/setup_actions.php', { method:'POST', body: fd });
            const json = await res.json();
            if (json.success) {
                Swal.fire({ icon:'success', title:'Proceso Éxitoso', text: json.message });
            } else {
                Swal.fire({ icon:'error', title:'Error', text: json.error });
            }
        } catch(e) { Swal.fire({ icon:'error', title:'Error Fatal', text: e.message }); }
    }
}

async function uploadSQL(input) {
    if (!input.files[0]) return;
    
    Swal.fire({ title: 'Importando SQL...', text: 'No refresque la página.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    const fd = new FormData();
    fd.append('action', 'import_sql');
    fd.append('file', input.files[0]);

    try {
        const res = await fetch('api/setup_actions.php', { method:'POST', body: fd });
        const json = await res.json();
        if (json.success) {
            Swal.fire({ icon:'success', title:'Base de datos actualizada', text: json.message }).then(() => location.reload());
        } else {
            Swal.fire({ icon:'error', title:'Error en SQL', text: json.error });
        }
    } catch(e) { Swal.fire({ icon:'error', title:'Fallo Crítico', text: e.message }); }
}
</script>

</body>
</html>
