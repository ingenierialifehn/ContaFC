<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requireRol('admin'); // Solo el administrador global gestiona usuarios

$user    = Auth::user();
$empresa = null;
$empresasDisponibles = [];

try {
    $db = Database::getInstance()->getPdo();
    $empresa = $db->prepare("SELECT * FROM empresas WHERE id = :id");
    $empresa->execute([':id' => Auth::empresaId()]);
    $empresa = $empresa->fetch();
    
    $empresasDisponibles = $db->query("SELECT id, nombre FROM empresas WHERE activa = 1 ORDER BY nombre")->fetchAll();
} catch (\Throwable $e) {}

$activeNav = 'usuarios';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios | ContaFC RBAC</title>
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
</head>
<body class="h-full font-sans flex text-sm overflow-hidden">

<?php require __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-1 flex flex-col min-w-0">
    <!-- Header -->
    <header class="bg-white border-b border-slate-200 px-8 py-5 flex items-center justify-between z-10 shadow-sm">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">Control de Usuarios (RBAC)</h1>
            <p class="text-slate-500 text-xs mt-0.5">Administración centralizada de accesos y roles multi-empresa.</p>
        </div>
        <button onclick="abrirModalUsuario()" 
                class="bg-slate-900 px-6 py-3 text-white rounded-2xl hover:bg-slate-800 transition font-bold shadow-lg shadow-slate-900/10 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            Crear Usuario
        </button>
    </header>

    <div class="flex-1 overflow-auto p-8">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Listado de Personal</span>
                <div class="flex gap-2">
                    <span class="flex items-center gap-1 text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100 italic">
                        <div class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></div> Seguridad Activa
                    </span>
                </div>
            </div>
            
            <table class="w-full text-left">
                <thead>
                    <tr class="text-slate-400 text-[10px] uppercase font-bold tracking-widest border-b border-slate-100 bg-white">
                        <th class="px-8 py-5">Perfil de Usuario</th>
                        <th class="px-8 py-5">Rol / Permisos</th>
                        <th class="px-8 py-5 text-center">Empresas Asignadas</th>
                        <th class="px-8 py-5 text-center">Estado</th>
                        <th class="px-8 py-5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="usuarios-body" class="divide-y divide-slate-50">
                    <!-- Loaded via JS -->
                </tbody>
            </table>
            
            <div id="loading-state" class="p-20 text-center text-slate-400">
                <svg class="animate-spin w-8 h-8 mx-auto mb-4 text-slate-300" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                <p class="font-medium">Sincronizando directorio...</p>
            </div>
        </div>
    </div>
</main>

<script>
const EMPRESAS = <?= json_encode($empresasDisponibles) ?>;
let usuarios = [];

document.addEventListener('DOMContentLoaded', cargarUsuarios);

async function cargarUsuarios() {
    document.getElementById('loading-state').classList.remove('hidden');
    const res = await fetch('<?= BASE_URL ?>/api/usuarios.php');
    const json = await res.json();
    usuarios = json.data || [];
    document.getElementById('loading-state').classList.add('hidden');
    renderUsuarios();
}

function renderUsuarios() {
    const body = document.getElementById('usuarios-body');
    body.innerHTML = usuarios.map(u => `
        <tr class="hover:bg-slate-50 transition-all group border-l-4 border-transparent hover:border-honduras">
            <td class="px-8 py-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 text-slate-500 flex items-center justify-center font-black text-sm border border-slate-200 uppercase shadow-sm">
                        ${u.username.substring(0, 2)}
                    </div>
                    <div>
                        <div class="font-extrabold text-slate-800 text-base tracking-tight">${u.nombre}</div>
                        <div class="text-[11px] text-slate-400 flex items-center gap-1 font-mono uppercase">
                             <span class="font-bold text-honduras">${u.username}</span> • ${u.email || 'sin@correo.hn'}
                        </div>
                    </div>
                </div>
            </td>
            <td class="px-8 py-6">
                <div class="flex flex-col gap-1">
                    <span class="px-2.5 py-1 rounded-lg text-[9px] uppercase font-black tracking-widest inline-block w-fit ${rolColor(u.rol)}">
                        ${u.rol}
                    </span>
                    <div class="text-[9px] text-slate-300 flex gap-1 items-center">
                        <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2V7a5 5 0 00-5-5zM7 7a3 3 0 116 0v2H7V7z"></path></svg>
                        ${Object.keys(u.permisos || {}).length} módulos activos
                    </div>
                </div>
            </td>
            <td class="px-8 py-6 text-center">
                <div class="flex justify-center -space-x-3 hover:space-x-1 transition-all">
                    ${(u.empresas || []).map((e, idx) => `
                        <div class="w-9 h-9 rounded-xl bg-white border-2 border-slate-50 flex items-center justify-center text-[10px] font-black text-slate-700 shadow-md ring-2 ring-white" title="${e.nombre}">
                            ${e.nombre.charAt(0)}
                        </div>
                    `).join('')}
                    ${!u.empresas?.length ? '<span class="text-slate-300 italic text-[10px]">Empresa Huérfana</span>' : ''}
                </div>
            </td>
            <td class="px-8 py-6 text-center">
                <button onclick="toggleEstado(${u.id}, ${u.activo})" class="relative inline-flex items-center cursor-pointer">
                    <span class="px-3 py-1.5 rounded-2xl text-[10px] font-bold ${u.activo == 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'} border border-transparent hover:border-current transition-all">
                        ${u.activo == 1 ? '● ACTIVO' : '○ INACTIVO'}
                    </span>
                </button>
            </td>
            <td class="px-8 py-6 text-right">
                <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button onclick="editarUsuario(${u.id})" class="p-2.5 bg-white border border-slate-200 text-slate-500 hover:text-honduras hover:border-honduras rounded-xl shadow-sm transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </button>
                    ${u.rol !== 'admin' ? `
                    <button onclick="borrarUsuario(${u.id})" class="p-2.5 bg-white border border-slate-200 text-slate-400 hover:text-red-600 hover:border-red-600 rounded-xl shadow-sm transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join('');
}

function rolColor(rol) {
    if (rol === 'admin') return 'bg-purple-600 text-white';
    if (rol === 'contador') return 'bg-honduras text-white';
    if (rol === 'auditor') return 'bg-amber-500 text-white';
    return 'bg-slate-200 text-slate-600';
}

async function toggleEstado(id, current) {
    const res = await fetch('<?= BASE_URL ?>/api/usuarios.php', {
        method: 'PATCH',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id: id, activo: current == 1 ? 0 : 1 })
    });
    if (res.ok) cargarUsuarios();
}

function abrirModalUsuario(id = null) {
    const u = id ? usuarios.find(x => x.id == id) : { username:'', nombre:'', email:'', rol:'consulta', empresas:[], permisos:{} };
    
    const empresasHtml = EMPRESAS.map(e => `
        <label class="group flex items-center justify-between p-3 bg-white border border-slate-200 rounded-2xl cursor-pointer hover:border-honduras transition-all">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-[10px] font-bold text-slate-400 group-hover:bg-blue-50 group-hover:text-honduras transition-all">${e.id}</div>
                <span class="text-xs font-bold text-slate-700">${e.nombre}</span>
            </div>
            <input type="checkbox" name="empresa_ids" value="${e.id}" ${u.empresas.some(ue => ue.id == e.id) ? 'checked' : ''} class="w-5 h-5 text-honduras rounded-lg border-slate-300 focus:ring-honduras">
        </label>
    `).join('');

    const MODULOS_DEF = [
        // General
        { k: 'dashboard',    l: '📊 Resumen Global' },
        { k: 'reportes',     l: '📈 Reportes & Balances' },
        // Comercial
        { k: 'pos',          l: '🏪 Punto de Venta' },
        { k: 'factura',      l: '🧾 Facturación SAR' },
        { k: 'productos',    l: '📦 Inventario / Kits' },
        { k: 'logistica',    l: '🚚 Logística y Envíos' },
        { k: 'contratos',    l: '🔁 Facturación Recurrente' },
        { k: 'devoluciones', l: '↩️ Notas de Crédito' },
        // Contabilidad
        { k: 'asiento',      l: '📝 Asientos de Diario' },
        { k: 'comprobantes', l: '📋 Comprobantes' },
        { k: 'activos',      l: '🏗️ Activos Fijos' },
        { k: 'tesoreria',    l: '🏦 Bancos y Tesorería' },
        { k: 'recurrente',   l: '💸 Egreso Recurrente' },
        { k: 'cecos',        l: '🎯 Centros de Costo' },
        { k: 'puc',          l: '📒 Catálogo PUC' },
        { k: 'auditoria',    l: '🔍 Auditoría & Logs' },
        { k: 'certificados', l: '📜 Certificados SAR' },
        { k: 'libros',       l: '📚 Libros Oficiales' },
        { k: 'terceros',     l: '👥 Clientes y Proveedores' },
        // Cartera
        { k: 'cartera',      l: '💳 Créditos y Recaudos' },
        // Administración
        { k: 'usuarios',     l: '👤 Gestión de Personal' },
        { k: 'empresas',     l: '🏢 Ajustes Multiempresa' },
        { k: 'cai',          l: '🔑 Resoluciones CAI' },
        { k: 'migracion',    l: '📂 Migración GDB/SQL' },
        { k: 'backups',      l: '💾 Backups del Sistema' },
        { k: 'setup_datos',  l: '⚙️ Mantenimiento de Datos' },
    ];

    const permisosHtml = `
        <div class="overflow-x-auto border border-slate-100 rounded-3xl">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-2 text-[9px] font-black text-slate-400 uppercase tracking-widest">Módulo</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-slate-400 uppercase tracking-widest w-12">Ver</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-slate-400 uppercase tracking-widest w-12">Crear</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-slate-400 uppercase tracking-widest w-12">Editar</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-slate-400 uppercase tracking-widest w-12">Borrar</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-slate-400 uppercase tracking-widest w-14">Todo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    ${MODULOS_DEF.map(m => {
                        const pm = u.permisos?.[m.k] || {};
                        const isLegacy = (typeof pm === 'boolean' && pm === true);
                        const vr = isLegacy ? true : !!pm.r;
                        const vc = isLegacy ? true : !!pm.c;
                        const vu = isLegacy ? true : !!pm.u;
                        const vd = isLegacy ? true : !!pm.d;
                        return `
                        <tr class="hover:bg-indigo-50/30 transition-colors">
                            <td class="px-4 py-2 font-bold text-slate-600 text-xs whitespace-nowrap">${m.l}</td>
                            <td class="px-2 py-2 text-center"><input type="checkbox" data-mod="${m.k}" data-action="r" ${vr?'checked':''} class="perm-check w-4 h-4 text-blue-600 rounded border-slate-300"></td>
                            <td class="px-2 py-2 text-center"><input type="checkbox" data-mod="${m.k}" data-action="c" ${vc?'checked':''} class="perm-check w-4 h-4 text-emerald-600 rounded border-slate-300"></td>
                            <td class="px-2 py-2 text-center"><input type="checkbox" data-mod="${m.k}" data-action="u" ${vu?'checked':''} class="perm-check w-4 h-4 text-amber-500 rounded border-slate-300"></td>
                            <td class="px-2 py-2 text-center"><input type="checkbox" data-mod="${m.k}" data-action="d" ${vd?'checked':''} class="perm-check w-4 h-4 text-red-500 rounded border-slate-300"></td>
                            <td class="px-2 py-2 text-center">
                                <button type="button" onclick="toggleAllPerms('${m.k}')" class="text-[8px] font-black text-slate-400 hover:text-indigo-600 uppercase tracking-widest transition">Todo</button>
                            </td>
                        </tr>`;
                    }).join('')}
                </tbody>
            </table>
        </div>
    `;

    Swal.fire({
        title: `<div class="text-xl font-black py-2">${id ? 'Actualizar Privilegios' : 'Alta de Nuevo Usuario'}</div>`,
        width: '800px',
        background: '#fff',
        html: `
            <div class="text-left space-y-6 max-h-[70vh] overflow-y-auto px-4 pt-4 custom-scroll">
                <div class="grid grid-cols-2 gap-5">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ID de Acceso (Usuario)</label>
                        <input id="sw_username" value="${u.username}" placeholder="Ej: fhernandez" 
                               class="w-full h-12 border border-slate-200 rounded-2xl px-4 outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50 text-sm font-bold">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombre Completo</label>
                        <input id="sw_nombre" value="${u.nombre}" placeholder="Nombre y Apellidos" 
                               class="w-full h-12 border border-slate-200 rounded-2xl px-4 outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50 text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Email Institucional</label>
                        <input id="sw_email" type="email" value="${u.email||''}" placeholder="usuario@empresa.hn" 
                               class="w-full h-12 border border-slate-200 rounded-2xl px-4 outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50 text-sm">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Rol de Sistema</label>
                        <select id="sw_rol" class="w-full h-12 border border-slate-200 rounded-2xl px-4 outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white text-sm font-bold">
                            <option value="consulta" ${u.rol==='consulta'?'selected':''}>Consulta (Solo lectura)</option>
                            <option value="contador" ${u.rol==='contador'?'selected':''}>Contador (Operativo)</option>
                            <option value="auditor"  ${u.rol==='auditor' ?'selected':''}>Auditor (Revisión)</option>
                            <option value="admin"    ${u.rol==='admin'   ?'selected':''}>Administrador (Global)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">${id ? 'Nueva Contraseña (dejar vacío = sin cambios)' : 'Contraseña Provisional *'}</label>
                        <input id="sw_pass" type="password" placeholder="Mínimo 6 caracteres" 
                               class="w-full h-12 border border-slate-200 rounded-2xl px-4 outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 bg-slate-50/50 text-sm">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Confirmar Contraseña</label>
                        <input id="sw_pass2" type="password" placeholder="Repetir contraseña" 
                               class="w-full h-12 border border-slate-200 rounded-2xl px-4 outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 bg-slate-50/50 text-sm">
                    </div>
                </div>

                <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-4">Empresas con Acceso Permitido</label>
                    <div class="grid grid-cols-2 gap-3">
                        ${empresasHtml}
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-3">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Capacidades del Módulo (CRUD)</label>
                        <button type="button" onclick="selectAllPerms(true)"  class="text-[9px] font-black text-emerald-600 hover:underline uppercase">✓ Todo</button>
                        <button type="button" onclick="selectAllPerms(false)" class="text-[9px] font-black text-rose-500 hover:underline uppercase">✕ Ninguno</button>
                    </div>
                    ${permisosHtml}
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: id ? 'Guardar Cambios' : 'Crear Usuario',
        cancelButtonText: 'Cancelar',
        buttonsStyling: false,
        customClass: {
            confirmButton: 'px-8 py-3.5 bg-slate-900 text-white rounded-2xl font-bold ml-3 text-sm',
            cancelButton:  'px-8 py-3.5 bg-slate-100 text-slate-500 rounded-2xl font-bold text-sm'
        },
        preConfirm: () => {
            const pass  = document.getElementById('sw_pass').value;
            const pass2 = document.getElementById('sw_pass2').value;
            const empIds = Array.from(document.querySelectorAll('input[name="empresa_ids"]:checked')).map(x => parseInt(x.value));
            const perms  = {};

            if (pass && pass !== pass2) {
                Swal.showValidationMessage('Las contraseñas no coinciden.');
                return false;
            }
            if (!id && !pass) {
                Swal.showValidationMessage('La contraseña es obligatoria para nuevos usuarios.');
                return false;
            }

            document.querySelectorAll('.perm-check').forEach(chk => {
                const mod = chk.dataset.mod;
                const action = chk.dataset.action;
                if (!perms[mod]) perms[mod] = { r:0, c:0, u:0, d:0 };
                if (chk.checked) perms[mod][action] = 1;
            });

            const data = {
                id:          id || null,
                username:    document.getElementById('sw_username').value.trim(),
                nombre:      document.getElementById('sw_nombre').value.trim(),
                email:       document.getElementById('sw_email').value.trim(),
                rol:         document.getElementById('sw_rol').value,
                empresa_ids: empIds,
                permisos:    perms,
            };
            if (pass) data.password = pass;

            if (!data.username || !data.nombre) {
                Swal.showValidationMessage('Usuario y Nombre son obligatorios.');
                return false;
            }
            return data;
        }
    }).then(res => {
        if (res.isConfirmed) guardarUsuario(res.value);
    });
}

async function guardarUsuario(data) {
    const isNew  = !data.id;
    const method = isNew ? 'POST' : 'PUT';
    try {
        Swal.showLoading();
        const res = await fetch('<?= BASE_URL ?>/api/usuarios.php', {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        if (json.success) {
            Swal.fire({ icon:'success', title: isNew ? '¡Usuario Creado!' : 'Cambios Guardados', text: json.message || '', timer:1800, showConfirmButton:false });
            cargarUsuarios();
        } else {
            Swal.fire({ icon:'error', title:'Error', text: json.error || 'No se pudo procesar el usuario.' });
        }
    } catch(e) {
        Swal.fire({ icon:'error', title:'Error de red', text: e.message });
    }
}

// Helpers de permisos
function selectAllPerms(val) {
    document.querySelectorAll('.perm-check').forEach(c => c.checked = val);
}
function toggleAllPerms(mod) {
    const checks = document.querySelectorAll(`.perm-check[data-mod="${mod}"]`);
    const allOn  = Array.from(checks).every(c => c.checked);
    checks.forEach(c => c.checked = !allOn);
}


    const permisosHtml = `
        <div class="overflow-x-auto border border-slate-100 rounded-3xl">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-2 text-[9px] font-black text-slate-400 uppercase tracking-widest">Módulo</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-slate-400 uppercase tracking-widest w-12">Ver</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-slate-400 uppercase tracking-widest w-12">Crear</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-slate-400 uppercase tracking-widest w-12">Edit</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-slate-400 uppercase tracking-widest w-12">Del</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    ${MODULOS_DEF.map(m => {
                        const pm = u.permisos?.[m.k] || {};
                        // Manejar retrocompatibilidad si era booleano
                        const isLegacy = (typeof pm === 'boolean' && pm === true);
                        const r = isLegacy ? true : !!pm.r;
                        const c = isLegacy ? true : !!pm.c;
                        const u_perm = isLegacy ? true : !!pm.u;
                        const d = isLegacy ? true : !!pm.d;

                        return `
                        <tr class="hover:bg-indigo-50/30 transition-colors">
                            <td class="px-4 py-2.5 font-bold text-slate-600 text-xs">${m.l}</td>
                            <td class="px-2 py-2.5 text-center">
                                <input type="checkbox" data-mod="${m.k}" data-action="r" ${r ? 'checked' : ''} class="perm-check w-4 h-4 text-honduras rounded border-slate-300">
                            </td>
                            <td class="px-2 py-2.5 text-center">
                                <input type="checkbox" data-mod="${m.k}" data-action="c" ${c ? 'checked' : ''} class="perm-check w-4 h-4 text-honduras rounded border-slate-300">
                            </td>
                            <td class="px-2 py-2.5 text-center">
                                <input type="checkbox" data-mod="${m.k}" data-action="u" ${u_perm ? 'checked' : ''} class="perm-check w-4 h-4 text-honduras rounded border-slate-300">
                            </td>
                            <td class="px-2 py-2.5 text-center">
                                <input type="checkbox" data-mod="${m.k}" data-action="d" ${d ? 'checked' : ''} class="perm-check w-4 h-4 text-honduras rounded border-slate-300">
                            </td>
                        </tr>`;
                    }).join('')}
                </tbody>
            </table>
        </div>
    `;

    Swal.fire({
        title: `<div class="text-xl font-black py-2">${id ? 'Actualizar Privilegios' : 'Alta de Nuevo Usuario'}</div>`,
        width: '750px',
        background: '#fff',
        html: `
            <div class="text-left space-y-6 max-h-[70vh] overflow-y-auto px-4 pt-4 custom-scroll">
                <div class="grid grid-cols-2 gap-5">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ID de Acceso (Usuario)</label>
                        <input id="sw_username" value="${u.username}" placeholder="Ej: fhernandez" 
                               class="w-full h-12 border border-slate-200 rounded-2xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras bg-slate-50/50 text-sm font-bold">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombre Completo</label>
                        <input id="sw_nombre" value="${u.nombre}" placeholder="Nombre y Apellidos" 
                               class="w-full h-12 border border-slate-200 rounded-2xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras bg-slate-50/50 text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Email Institucional</label>
                        <input id="sw_email" type="email" value="${u.email||''}" placeholder="usuario@empresa.hn" 
                               class="w-full h-12 border border-slate-200 rounded-2xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras bg-slate-50/50 text-sm">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Rol de Sistema</label>
                        <select id="sw_rol" class="w-full h-12 border border-slate-200 rounded-2xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras bg-white text-sm font-bold">
                            <option value="consulta" ${u.rol==='consulta'?'selected':''}>Consulta (Solo lectura)</option>
                            <option value="contador" ${u.rol==='contador'?'selected':''}>Contador (Operativo)</option>
                            <option value="auditor" ${u.rol==='auditor'?'selected':''}>Auditor (Revisión)</option>
                            <option value="admin" ${u.rol==='admin'?'selected':''}>Administrador (Global)</option>
                        </select>
                    </div>
                </div>

                ${!id ? `
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Contraseña Provisional</label>
                    <input id="sw_pass" type="password" placeholder="Mínimo 8 caracteres" 
                           class="w-full h-12 border border-slate-200 rounded-2xl px-4 outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 bg-slate-50/50 text-sm">
                </div>` : ''}
                
                <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-4">Empresas con Acceso Permitido</label>
                    <div class="grid grid-cols-2 gap-3">
                        ${empresasHtml}
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-3">Capacidades del Módulo (CRUD)</label>
                    ${permisosHtml}
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'Procesar Usuario',
        buttonsStyling: false,
        customClass: {
            confirmButton: 'px-8 py-3.5 bg-slate-900 text-white rounded-2xl font-bold ml-3',
            cancelButton: 'px-8 py-3.5 bg-slate-100 text-slate-500 rounded-2xl font-bold'
        },
        preConfirm: () => {
             const empIds = Array.from(document.querySelectorAll('input[name="empresa_ids"]:checked')).map(x => parseInt(x.value));
             const perms  = {};
             
             document.querySelectorAll('.perm-check').forEach(chk => {
                 const mod = chk.dataset.mod;
                 const action = chk.dataset.action;
                 if (!perms[mod]) perms[mod] = { r:0, c:0, u:0, d:0 };
                 if (chk.checked) perms[mod][action] = 1;
             });
             
             const data = {
                 id: id,
                 username: document.getElementById('sw_username').value,
                 nombre: document.getElementById('sw_nombre').value,
                 email: document.getElementById('sw_email').value,
                 rol: document.getElementById('sw_rol').value,
                 empresa_ids: empIds,
                 permisos: perms
             };
             if (!id) data.password = document.getElementById('sw_pass').value;
             
             if (!data.username || !data.nombre) {
                 Swal.showValidationMessage('Usuario y Nombre son obligatorios');
                 return false;
             }
             if (!id && !data.password) {
                Swal.showValidationMessage('La contraseña es obligatoria para nuevos usuarios');
                return false;
             }
             return data;
        }
    }).then(res => {
        if (res.isConfirmed) guardarUsuario(res.value);
    });
}

async function guardarUsuario(data) {
    const res = await fetch('<?= BASE_URL ?>/api/usuarios.php', {
        method: data.id ? 'PUT' : 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    if (res.ok) {
        Swal.fire({icon:'success', title:'Guardado exitosamente', timer:1500, showConfirmButton:false});
        cargarUsuarios();
    } else {
        const err = await res.json();
        Swal.fire({icon:'error', title:'Error de Seguridad', text: err.error || 'No se pudo procesar el usuario.'});
    }
}

async function borrarUsuario(id) {
    const res = await Swal.fire({
        title: '¿Confirmar Baja?',
        text: 'Se revocará todo acceso de forma inmediata. Los logs de auditoría permanecerán inmutables.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Dar de Baja',
        customClass: { confirmButton: 'bg-rose-600 text-white px-6 py-2 rounded-xl ml-2', cancelButton: 'bg-slate-100 text-slate-500 px-6 py-2 rounded-xl' },
        buttonsStyling: false
    });
    if (res.isConfirmed) {
        const delRes = await fetch(`<?= BASE_URL ?>/api/usuarios.php?id=${id}`, { method: 'DELETE' });
        if (delRes.ok) cargarUsuarios();
    }
}

function editarUsuario(id) {
    abrirModalUsuario(id);
}
</script>

<style>
.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
</style>

</body>
</html>
