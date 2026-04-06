<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();

$user    = Auth::user();
$empresa = null;
$tipos_comp = [];
$usuarios = [];
try {
    $db = Database::getInstance()->getPdo();
    $empresa = $db->query("SELECT * FROM empresas WHERE id = " . Auth::empresaId())->fetch();
    $tipos_comp = $db->query("SELECT * FROM tipos_comprobante WHERE empresa_id = " . Auth::empresaId())->fetchAll();
    $usuarios = $db->query("SELECT id, username FROM usuarios WHERE empresa_id = " . Auth::empresaId())->fetchAll();
} catch (\Throwable $e) {}

$activeNav = 'auditoria';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50 text-slate-700">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría: Logs y Consecutivos – <?= htmlspecialchars($empresa['nombre'] ?? 'ContaFC') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT:'#1e3a5f', light:'#2563eb', dark:'#0f1f3d' },
                        honduras: '#0369a1',
                    },
                    fontFamily: { sans: ['Inter','system-ui','sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .audit-badge-insert { background:#f0fdf4; color:#166534; }
        .audit-badge-update { background:#eff6ff; color:#1e40af; }
        .audit-badge-delete { background:#fef2f2; color:#991b1b; }
        .audit-badge-login  { background:#faf5ff; color:#6b21a8; }
    </style>
</head>
<body class="h-full font-sans flex text-sm overflow-hidden text-slate-700">

<?php require __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-1 flex flex-col min-w-0 bg-slate-50">
    <!-- Header -->
    <header class="bg-white border-b border-slate-200 px-8 py-5 flex items-center justify-between z-10 shadow-sm">
        <div class="flex items-center gap-6">
            <div class="w-14 h-14 bg-slate-900 text-white rounded-3xl flex items-center justify-center shadow-inner group">
                 <svg class="w-8 h-8 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <div>
                <nav class="flex text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 gap-2">
                    <span class="text-honduras">Auditoría & Cumplimiento</span>
                    <span>/</span>
                    <span>Honduras 2026</span>
                </nav>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight leading-none">Módulo de Auditoría</h1>
                <p class="text-slate-500 text-xs mt-1">Visor de logs transaccionales y de seguridad.</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
             <button onclick="cargarLogs()" 
                    class="bg-slate-800 px-6 py-3 text-white rounded-2xl hover:bg-slate-900 transition font-bold shadow-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Actualizar Logs
            </button>
        </div>
    </header>

    <div class="flex-1 overflow-auto p-8">
        <div class="flex flex-col gap-8">
            
            <!-- Filtros de Auditoría -->
            <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                    <div class="space-y-1">
                        <label class="text-[10px] items-center gap-1 font-bold text-slate-400 uppercase tracking-widest">Desde</label>
                        <input id="f_desde" type="date" value="<?= date('Y-m-d') ?>" class="w-full h-11 border border-slate-200 rounded-xl px-4 outline-none text-xs font-bold">
                    </div>
                    <div class="space-y-1">
                         <label class="text-[10px] items-center gap-1 font-bold text-slate-400 uppercase tracking-widest">Hasta</label>
                        <input id="f_hasta" type="date" value="<?= date('Y-m-d') ?>" class="w-full h-11 border border-slate-200 rounded-xl px-4 outline-none text-xs font-bold">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] items-center gap-1 font-bold text-slate-400 uppercase tracking-widest">Colaborador</label>
                        <select id="f_user" class="w-full h-11 border border-slate-200 rounded-xl px-4 outline-none text-xs font-black bg-slate-50">
                            <option value="">Todos...</option>
                            <?php foreach($usuarios as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= $u['username'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="space-y-1">
                         <label class="text-[10px] items-center gap-1 font-bold text-slate-400 uppercase tracking-widest">Acción</label>
                        <select id="f_accion" class="w-full h-11 border border-slate-200 rounded-xl px-4 outline-none text-xs font-black bg-slate-50">
                            <option value="">Todas...</option>
                            <option value="INSERT">Creaciones (INSERT)</option>
                            <option value="UPDATE">Modificaciones (UPDATE)</option>
                            <option value="DELETE">Eliminaciones (DELETE)</option>
                            <option value="LOGIN">Ingresos (LOGIN)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Tabla de Logs -->
            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl overflow-hidden">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-slate-400 text-[10px] uppercase font-bold tracking-widest border-b border-slate-100 bg-slate-50/50">
                            <th class="px-8 py-5">Momento</th>
                            <th class="px-8 py-5">Usuario / IP</th>
                            <th class="px-8 py-5">Entidad / ID</th>
                            <th class="px-8 py-5 text-center">Acción</th>
                            <th class="px-8 py-5">Detalles</th>
                        </tr>
                    </thead>
                    <tbody id="logs-body" class="divide-y divide-slate-50">
                         <tr><td colspan="5" class="text-center py-20 text-slate-400 italic font-medium">Buscando registros de auditoría...</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Sección de Consecutivos (Audit HND) -->
            <div class="bg-slate-900 rounded-[2.5rem] p-10 text-white shadow-2xl flex items-center justify-between border-b-8 border-honduras">
                <div class="flex items-center gap-6">
                    <div class="w-20 h-20 bg-white/5 rounded-3xl flex items-center justify-center">
                        <svg class="w-10 h-10 text-honduras" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-2xl font-black tracking-tight mb-1">Auditoría de Consecutivos (Brechas)</h3>
                        <p class="text-slate-400 max-w-lg text-xs leading-relaxed font-medium">Detecta saltos en la numeración de facturas o comprobantes. Los saltos pueden indicar registros eliminados sin autorización o errores críticos en la secuencia legal.</p>
                    </div>
                </div>
                <div class="flex flex-col gap-3">
                    <button onclick="auditarConsecutivos('comprobantes')" class="px-8 py-4 bg-honduras text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:opacity-90 transition shadow-lg">Comprobantes</button>
                    <button onclick="auditarConsecutivos('facturas')" class="px-8 py-4 bg-white/10 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-white/20 transition">Facturación SAR</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
let logs = [];

document.addEventListener('DOMContentLoaded', cargarLogs);

async function cargarLogs() {
    const filters = {
        usuario_id: document.getElementById('f_user').value,
        accion: document.getElementById('f_accion').value,
        desde: document.getElementById('f_desde').value,
        hasta: document.getElementById('f_hasta').value
    };
    const params = new URLSearchParams(filters).toString();
    const res = await fetch(`<?= BASE_URL ?>/api/auditoria.php?action=logs&${params}`);
    const json = await res.json();
    logs = json.data || [];
    renderLogs();
}

function renderLogs() {
    const body = document.getElementById('logs-body');
    if (!logs.length) {
        body.innerHTML = '<tr><td colspan="5" class="text-center py-20 text-slate-400">No se encontraron movimientos registrados con esos filtros.</td></tr>';
        return;
    }

    body.innerHTML = logs.map(l => {
        const ctg = l.accion.toLowerCase();
        return `
        <tr class="hover:bg-slate-50/80 transition-all group">
            <td class="px-8 py-5">
                <div class="flex flex-col">
                    <span class="font-black text-slate-800 tracking-tight">${l.created_at.split(' ')[1]}</span>
                    <span class="text-[10px] text-slate-400 uppercase font-black">${l.created_at.split(' ')[0]}</span>
                </div>
            </td>
            <td class="px-8 py-5">
                <div class="flex flex-col">
                    <span class="font-bold text-slate-700">${l.usuario_nombre || 'SISTEMA'}</span>
                    <span class="text-[10px] font-mono text-slate-400">${l.ip || 'Local'}</span>
                </div>
            </td>
            <td class="px-8 py-5">
                 <div class="flex flex-col">
                    <span class="font-black text-[10px] uppercase text-honduras tracking-widest">${l.tabla}</span>
                    <span class="font-bold text-slate-500">ID: ${l.registro_id}</span>
                </div>
            </td>
            <td class="px-8 py-5 text-center">
                <span class="px-4 py-1.5 rounded-xl text-[10px] font-black tracking-widest uppercase audit-badge-${ctg}">
                    ${l.accion}
                </span>
            </td>
            <td class="px-8 py-5">
                <button onclick="verDataLog(${l.id})" class="text-honduras hover:underline font-bold text-xs">Ver cambios JSON</button>
            </td>
        </tr>
    `}).join('');
}

async function auditarConsecutivos(tabla) {
    Swal.fire({ title:'Auditoría de Secuencia...', didOpen: () => Swal.showLoading() });
    const res = await fetch(`<?= BASE_URL ?>/api/auditoria.php?action=consecutivos&tabla=${tabla}`);
    const j = await res.json();
    const data = j.data;

    Swal.fire({
        title: `Resultado para: ${tabla.toUpperCase()}`,
        html: `
            <div class="text-left space-y-4 p-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                        <p class="text-[10px] font-black uppercase text-slate-400 mb-1">Total Registros</p>
                        <p class="text-3xl font-black text-slate-800">${data.count}</p>
                    </div>
                    <div class="p-4 ${data.total_gaps > 0 ? 'bg-rose-50 border-rose-100' : 'bg-emerald-50 border-emerald-100'} rounded-2xl border">
                        <p class="text-[10px] font-black uppercase text-slate-400 mb-1">Brechas Halladas</p>
                        <p class="text-3xl font-black ${data.total_gaps > 0 ? 'text-rose-600' : 'text-emerald-600'}">${data.total_gaps}</p>
                    </div>
                </div>
                ${data.total_gaps > 0 ? `
                    <p class="font-bold text-xs text-rose-500 uppercase tracking-widest">Numeración faltante:</p>
                    <div class="p-3 bg-white border border-rose-100 rounded-xl max-h-40 overflow-auto font-mono text-xs font-black text-rose-800">
                        ${data.gaps.join(', ')}
                    </div>
                    <div class="p-4 bg-orange-50 border border-orange-100 rounded-2xl italic text-[11px] text-orange-700">Se recomienda verificar el log de eliminaciones (DELETE) para estos números identificados.</div>
                ` : '<div class="p-8 text-center"><p class="font-black text-emerald-600 text-lg">✓ Secuencia Perfecta</p><p class="text-xs text-slate-400 mt-1">No se detectaron saltos en la numeración legal.</p></div>'}
            </div>
        `,
        width: '600px',
        confirmButtonText: 'Cerrar Informe Audit',
        customClass: { confirmButton: 'bg-slate-900 text-white px-8 py-3 rounded-2xl' }
    });
}

function verDataLog(id) {
    const log = logs.find(l => l.id == id);
    if(!log) return;
    
    Swal.fire({
        title: 'Detalle de Datos',
        html: `
            <div class="text-left font-mono text-[11px] p-4 bg-slate-950 text-emerald-400 rounded-2xl overflow-auto max-h-[70vh]">
                <p class="text-white/40 mb-2">// Registro ID: ${log.registro_id}</p>
                <div class="mb-4">
                    <p class="text-white uppercase font-black mb-1">ANTES:</p>
                    <pre class="bg-white/5 p-3 rounded-lg">${JSON.stringify(JSON.parse(log.datos_antes || '{}'), null, 2)}</pre>
                </div>
                <div>
                     <p class="text-white uppercase font-black mb-1">DESPUÉS:</p>
                    <pre class="bg-white/5 p-3 rounded-lg underline">${JSON.stringify(JSON.parse(log.datos_des || '{}'), null, 2)}</pre>
                </div>
            </div>
        `,
        width: '800px',
        background: '#020617',
        confirmButtonText: 'Cerrar Visor',
        customClass: { confirmButton: 'bg-white/10 text-white px-8 py-3 rounded-2xl border border-white/20' }
    });
}
</script>
</body>
</html>
