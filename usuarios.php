<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requireRol('admin');

$db = Database::getInstance()->getPdo();
$empresasDisponibles = $db->query("SELECT id, nombre FROM empresas ORDER BY nombre")->fetchAll();

$pageTitle = 'Control de Usuarios (RBAC)';
$activeNav = 'usuarios';
$b = BASE_URL;

// Obtener datos básicos para el título
$empresaActual = null;
try {
    $empresaActual = $db->query("SELECT nombre FROM empresas WHERE id = " . Auth::empresaId())->fetch();
} catch (\Throwable $e) {}

?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-[#f8fafc]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> – <?= htmlspecialchars($empresaActual['nombre'] ?? 'ContaFC') ?></title>
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .glass-panel { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.5); }
        .custom-scroll::-webkit-scrollbar { width: 5px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    </style>
</head>
<body class="h-full font-sans flex text-sm overflow-hidden bg-[#f8fafc]">

<?php require __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
    <!-- Header -->
    <div class="px-10 py-8 bg-white border-b border-slate-100 flex items-center justify-between z-10">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2 h-2 rounded-full bg-honduras animate-pulse"></span>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Administración Global</span>
            </div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tighter">Usuarios del <span class="italic text-honduras">Sistema</span></h1>
        </div>
        <button onclick="abrirModalUsuario()" class="bg-slate-900 text-white px-8 py-4 rounded-2xl font-black text-sm hover:bg-honduras transition-all shadow-xl shadow-slate-200 flex items-center gap-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            Nuevo Usuario
        </button>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-y-auto p-10 bg-[#f8fafc] custom-scroll">
        <div class="w-full">
            <div class="glass-panel rounded-[2.5rem] shadow-2xl shadow-slate-200/50 overflow-hidden relative min-h-[500px]">
                
                <!-- Search Box -->
                <div class="p-8 border-b border-slate-100/50 bg-white/30">
                    <div class="relative max-w-sm">
                        <svg class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" id="userInput" onkeyup="filtrar()" placeholder="Filtrar por nombre o usuario..." 
                               class="w-full pl-12 pr-4 py-3.5 bg-white border-2 border-slate-50 focus:border-honduras/20 rounded-2xl outline-none text-xs font-bold transition-all">
                    </div>
                </div>

                <!-- Table -->
                <div id="loader" class="absolute inset-0 z-50 flex flex-col items-center justify-center bg-white/90 backdrop-blur-sm transition-all duration-500">
                    <div class="w-12 h-12 border-4 border-slate-100 border-t-honduras rounded-full animate-spin mb-4"></div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest animate-pulse">Sincronizando Usuarios...</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                                <th class="px-10 py-6">Usuario & Correo</th>
                                <th class="px-10 py-6 text-center">Rol</th>
                                <th class="px-10 py-6 text-center">Empresas</th>
                                <th class="px-10 py-6 text-center">Estado</th>
                                <th class="px-10 py-6 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="user-body" class="divide-y divide-slate-50">
                            <!-- JS data -->
                        </tbody>
                    </table>
                </div>

                <div id="no-data" class="hidden py-32 text-center overflow-hidden">
                    <div class="text-4xl mb-4">🔍</div>
                    <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Sin coincidencias encontradas</p>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
const EMPRESAS = <?= json_encode($empresasDisponibles) ?>;
const MODULOS = [
    { cat: 'General', items: [
        { key: 'dashboard', label: 'Tablero (Dashboard)' },
        { key: 'reportes', label: 'Reportes y Balances' }
    ]},
    { cat: 'Ecosistema Comercial', items: [
        { key: 'pos', label: 'Punto de Venta (POS)' },
        { key: 'factura', label: 'Facturación SAR' },
        { key: 'productos', label: 'Inventario y Kits' },
        { key: 'logistica', label: 'Logística y Envíos' },
        { key: 'contratos', label: 'Fact. Recurrente' },
        { key: 'devoluciones', label: 'Notas de Crédito' }
    ]},
    { cat: 'Contabilidad Core', items: [
        { key: 'asiento', label: 'Asientos de Diario' },
        { key: 'comprobantes', label: 'Comprobantes' },
        { key: 'activos', label: 'Activos Fijos' },
        { key: 'tesoreria', label: 'Bancos y Tesorería' },
        { key: 'recurrente', label: 'Egreso Recurrente' },
        { key: 'cecos', label: 'Centros de Costo' },
        { key: 'proyectos', label: 'Gestión de Proyectos' },
        { key: 'auditoria', label: 'Auditoría & Logs' },
        { key: 'certificados', label: 'Certificados SAR' },
        { key: 'libros', label: 'Libros Oficiales' },
        { key: 'puc', label: 'Plan de Cuentas' },
        { key: 'terceros', label: 'Clientes y Proveedores' }
    ]},
    { cat: 'Cartera y Cobros', items: [
        { key: 'cartera', label: 'Créditos y Recaudos' }
    ]},
    { cat: 'Administración', items: [
        { key: 'usuarios', label: 'Seguridad (Usuarios)' },
        { key: 'empresas', label: 'Ajustes Multiempresa' },
        { key: 'cai', label: 'Resoluciones SAR (CAI)' },
        { key: 'backups', label: 'Copias de Seguridad' },
        { key: 'setup', label: 'Mantenimiento y Reseteo' }
    ]}
];

document.addEventListener('DOMContentLoaded', init);

async function init() {
    try {
        const r = await fetch('api/usuarios.php');
        if (!r.ok) {
            const r2 = r.clone();
            try {
                const errData = await r2.json();
                throw new Error(errData.error || `HTTP ${r.status}`);
            } catch(je) {
                const text = await r.text();
                throw new Error(`HTTP ${r.status}: ${text.substring(0, 100)}`);
            }
        }
        const res = await r.json();
        usuarios = res.data || [];
        render(usuarios);
    } catch (e) {
        console.error('INIT ERROR:', e);
        document.getElementById('user-body').innerHTML = `
            <tr>
                <td colspan="5" class="py-20 text-center">
                    <div class="inline-flex p-4 bg-rose-50 rounded-full mb-4">
                        <svg class="w-8 h-8 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <p class="text-rose-600 font-black uppercase tracking-widest text-[10px] mb-2">Error Crítico del Sistema</p>
                    <p class="text-slate-400 font-bold text-sm max-w-sm mx-auto">${e.message}</p>
                </td>
            </tr>
        `;
    } finally {
        const loader = document.getElementById('loader');
        if (loader) {
            loader.style.opacity = '0';
            setTimeout(() => loader.classList.add('hidden'), 500);
        }
    }
}

function render(data) {
    const body = document.getElementById('user-body');
    const noData = document.getElementById('no-data');
    
    if (data.length === 0) {
        body.innerHTML = '';
        noData.classList.remove('hidden');
        return;
    }
    
    noData.classList.add('hidden');
    body.innerHTML = data.map(u => `
        <tr class="hover:bg-slate-50/50 transition-all group">
            <td class="px-10 py-7">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center font-black text-slate-500 text-sm border-2 border-white shadow-sm group-hover:scale-105 transition-all">
                        ${u.nombre.charAt(0)}
                    </div>
                    <div>
                        <div class="font-black text-slate-800 text-base tracking-tight leading-none mb-1">${u.nombre}</div>
                        <div class="text-[10px] font-mono font-bold text-slate-400 uppercase tracking-tighter">
                            <span class="text-honduras">@${u.username}</span> • ${u.email || 'DESCONOCIDO'}
                        </div>
                    </div>
                </div>
            </td>
            <td class="px-10 py-7 text-center">
                <span class="px-3 py-1 rounded-xl text-[9px] font-black uppercase tracking-widest ${u.rol==='admin' ? 'bg-rose-600 text-white shadow-lg shadow-rose-200' : 'bg-slate-200 text-slate-600'}">
                    ${u.rol}
                </span>
            </td>
            <td class="px-10 py-7 text-center">
                <div class="flex justify-center -space-x-3">
                    ${(u.empresas || []).map(e => `
                        <div class="w-9 h-9 rounded-xl bg-white border-2 border-slate-50 flex items-center justify-center text-[10px] font-black text-slate-800 shadow-sm ring-4 ring-white" title="${e.nombre}">
                            ${e.nombre.charAt(0)}
                        </div>
                    `).join('')}
                </div>
            </td>
            <td class="px-10 py-7 text-center">
                <button onclick="toggle(${u.id}, ${u.activo})" class="text-[10px] font-black uppercase tracking-widest ${u.activo == 1 ? 'text-emerald-500' : 'text-rose-400'} hover:underline">
                    ${u.activo == 1 ? '● Activo' : '○ Inactivo'}
                </button>
            </td>
            <td class="px-10 py-7 text-right">
                <button onclick="abrirModalUsuario(${u.id})" class="p-3 bg-white border border-slate-100 text-slate-300 hover:text-honduras hover:border-honduras rounded-xl transition-all shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                </button>
            </td>
        </tr>
    `).join('');
}

function filtrar() {
    const q = document.getElementById('userInput').value.toLowerCase();
    render(usuarios.filter(u => u.nombre.toLowerCase().includes(q) || u.username.toLowerCase().includes(q)));
}

async function toggle(id, current) {
    await fetch('<?= BASE_URL ?>/api/usuarios.php', {
        method: 'PATCH',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id, activo: current == 1 ? 0 : 1 })
    });
    init();
}

function abrirModalUsuario(id = null) {
    const u = id ? usuarios.find(x => x.id == id) : { id:null, username:'', nombre:'', email:'', rol:'consulta', empresas:[], permisos:{} };
    
    // Asegurarnos que permisos sea un objeto
    if (typeof u.permisos !== 'object' || u.permisos === null) {
        u.permisos = {};
    }

    Swal.fire({
        title: id ? 'Editar Privilegios' : 'Nueva Cuenta',
        width: '900px',
        background: '#f8fafc',
        html: `
            <div class="text-left py-4 space-y-8 max-h-[80vh] overflow-y-auto pr-2 custom-scroll">
                <!-- Información Básica -->
                <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm">
                    <div class="text-[10px] font-black text-honduras uppercase tracking-[0.2em] mb-4">Información del Perfil</div>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Usuario</label>
                            <input id="sw_u" value="${u.username}" ${id?'disabled':''} class="w-full p-3.5 border-2 border-slate-50 rounded-2xl outline-none focus:border-honduras/30 transition-all text-sm font-black bg-slate-50/50">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombre Completo</label>
                            <input id="sw_n" value="${u.nombre}" class="w-full p-3.5 border-2 border-slate-50 rounded-2xl outline-none focus:border-honduras/30 transition-all text-sm font-bold bg-slate-50/50">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="relative">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Contraseña</label>
                            <div class="relative">
                                <input id="sw_p" type="password" placeholder="${id ? 'Dejar vacío para no cambiar' : 'Mínimo 6 caracteres'}" 
                                       class="w-full p-3.5 pr-12 border-2 border-slate-50 rounded-2xl outline-none focus:border-honduras/30 transition-all text-sm bg-slate-50/50">
                                <button type="button" onclick="togglePassVisibility()" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-honduras transition-colors">
                                    <svg id="eye_icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Rol Base</label>
                            <select id="sw_r" class="w-full p-3.5 border-2 border-slate-50 rounded-2xl outline-none focus:border-honduras/30 bg-slate-50/50 text-sm font-black">
                                <option value="consulta" ${u.rol==='consulta'?'selected':''}>Consulta (Sólo Lectura)</option>
                                <option value="contador" ${u.rol==='contador'?'selected':''}>Contador (Operativo)</option>
                                <option value="admin" ${u.rol==='admin'?'selected':''}>Administrador (Acceso Total)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Empresas -->
                <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm">
                    <div class="text-[10px] font-black text-honduras uppercase tracking-[0.2em] mb-4">Acceso a Entidades</div>
                    <div class="grid grid-cols-2 gap-2">
                        ${EMPRESAS.map(e => `
                        <label class="flex items-center gap-3 p-3.5 bg-slate-50/50 border border-transparent rounded-2xl cursor-pointer hover:border-honduras/20 transition-all group">
                            <input type="checkbox" name="eid" value="${e.id}" ${u.empresas.some(x => x.id == e.id) ? 'checked' : ''} class="w-5 h-5 text-honduras rounded-lg border-2 border-slate-200">
                            <span class="text-[11px] font-black text-slate-600 group-hover:text-slate-900">${e.nombre}</span>
                        </label>
                        `).join('')}
                    </div>
                </div>

                <!-- Permisos Detallados -->
                <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-[10px] font-black text-honduras uppercase tracking-[0.2em]">Permisos Críticos por Módulo</div>
                        <div class="flex gap-2">
                            <button onclick="checkAll(true)" type="button" class="text-[9px] font-black text-slate-400 hover:text-honduras uppercase tracking-widest">Marcar Todo</button>
                            <span class="text-slate-200">|</span>
                            <button onclick="checkAll(false)" type="button" class="text-[9px] font-black text-slate-400 hover:text-rose-500 uppercase tracking-widest">Limpiar</button>
                        </div>
                    </div>
                    
                    <div class="overflow-hidden border border-slate-50 rounded-2xl relative">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 border-b border-slate-100 sticky top-0 z-20">
                                <tr class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                                    <th class="px-6 py-4 bg-slate-50">Módulo</th>
                                    <th class="px-6 py-4 text-center bg-slate-50">Ver (R)</th>
                                    <th class="px-6 py-4 text-center bg-slate-50">Crear (C)</th>
                                    <th class="px-6 py-4 text-center bg-slate-50">Editar (U)</th>
                                    <th class="px-6 py-4 text-center bg-slate-50">Borrar (D)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                ${MODULOS.map(group => `
                                    <tr class="bg-slate-50/30">
                                        <td colspan="5" class="px-6 py-2 border-y border-slate-50">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[8px] font-black text-slate-400 uppercase tracking-[0.2em]">${group.cat}</span>
                                                <div class="flex gap-2">
                                                    <button onclick="checkAll(true, '${group.cat}')" type="button" class="text-[8px] font-black text-slate-300 hover:text-honduras uppercase tracking-widest transition-colors">Todo de ${group.cat}</button>
                                                    <span class="text-slate-100 text-[8px]">|</span>
                                                    <button onclick="checkAll(false, '${group.cat}')" type="button" class="text-[8px] font-black text-slate-300 hover:text-rose-400 uppercase tracking-widest transition-colors">Limpiar</button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    ${group.items.map(m => {
                                        const p = u.permisos[m.key] || {};
                                        return `
                                        <tr class="hover:bg-slate-50/30 transition-all">
                                            <td class="px-6 py-3.5">
                                                <div class="text-xs font-black text-slate-700">${m.label}</div>
                                                <div class="text-[9px] font-mono text-slate-300 uppercase">${m.key}</div>
                                            </td>
                                            <td class="px-6 py-3.5 text-center">
                                                <div class="flex flex-col items-center gap-1">
                                                    <span class="text-[7px] font-black text-slate-300 uppercase">Ver</span>
                                                    <input type="checkbox" data-mod="${m.key}" data-cat="${group.cat}" data-acc="r" ${p.r ? 'checked' : ''} class="w-4 h-4 text-honduras rounded border-slate-200">
                                                </div>
                                            </td>
                                            <td class="px-6 py-3.5 text-center">
                                                <div class="flex flex-col items-center gap-1">
                                                    <span class="text-[7px] font-black text-slate-300 uppercase">Crear</span>
                                                    <input type="checkbox" data-mod="${m.key}" data-cat="${group.cat}" data-acc="c" ${p.c ? 'checked' : ''} class="w-4 h-4 text-emerald-500 rounded border-slate-200">
                                                </div>
                                            </td>
                                            <td class="px-6 py-3.5 text-center">
                                                <div class="flex flex-col items-center gap-1">
                                                    <span class="text-[7px] font-black text-slate-300 uppercase">Editar</span>
                                                    <input type="checkbox" data-mod="${m.key}" data-cat="${group.cat}" data-acc="u" ${p.u ? 'checked' : ''} class="w-4 h-4 text-amber-500 rounded border-slate-200">
                                                </div>
                                            </td>
                                            <td class="px-6 py-3.5 text-center">
                                                <div class="flex flex-col items-center gap-1">
                                                    <span class="text-[7px] font-black text-slate-300 uppercase">Borrar</span>
                                                    <input type="checkbox" data-mod="${m.key}" data-cat="${group.cat}" data-acc="d" ${p.d ? 'checked' : ''} class="w-4 h-4 text-rose-500 rounded border-slate-200">
                                                </div>
                                            </td>
                                        </tr>
                                        `;
                                    }).join('')}
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-4 text-[9px] font-medium text-slate-400 italic">Nota: Los Administradores siempre tienen acceso total independientemente de estos checkboxes.</p>
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'Guardar Privilegios',
        cancelButtonText: 'Cancelar',
        customClass: { 
            confirmButton: 'px-10 py-4 bg-slate-900 text-white rounded-2xl font-black text-xs hover:bg-honduras transition-all shadow-xl shadow-slate-200 ml-4', 
            cancelButton: 'text-slate-400 font-black text-xs hover:text-slate-600 transition-all' 
        },
        buttonsStyling: false,
        preConfirm: () => {
            const data = {
                id: id,
                username: document.getElementById('sw_u').value,
                nombre: document.getElementById('sw_n').value,
                rol: document.getElementById('sw_r').value,
                password: document.getElementById('sw_p').value,
                empresa_ids: Array.from(document.querySelectorAll('input[name="eid"]:checked')).map(x => x.value),
                permisos: {}
            };

            // Recolectar permisos del grid
            MODULOS.forEach(group => {
                group.items.forEach(m => {
                    const r = document.querySelector(`input[data-mod="${m.key}"][data-acc="r"]`).checked;
                    const c = document.querySelector(`input[data-mod="${m.key}"][data-acc="c"]`).checked;
                    const u = document.querySelector(`input[data-mod="${m.key}"][data-acc="u"]`).checked;
                    const d = document.querySelector(`input[data-mod="${m.key}"][data-acc="d"]`).checked;
                    
                    if (r || c || u || d) {
                        data.permisos[m.key] = { r, c, u, d };
                    }
                });
            });

            if (!data.username || !data.nombre) { Swal.showValidationMessage('Datos incompletos'); return false; }
            if (!id && !data.password) { Swal.showValidationMessage('Contraseña obligatoria para nuevos usuarios'); return false; }
            
            return data;
        }
    }).then(res => {
        if (res.isConfirmed) save(res.value);
    });
}

function togglePassVisibility() {
    const p = document.getElementById('sw_p');
    const i = document.getElementById('eye_icon');
    if (p.type === 'password') {
        p.type = 'text';
        i.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>`;
    } else {
        p.type = 'password';
        i.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`;
    }
}

function checkAll(val, cat = null) {
    const selector = cat ? `input[data-cat="${cat}"]` : 'input[data-mod]';
    document.querySelectorAll(selector).forEach(i => i.checked = val);
}

async function save(data) {
    const res = await fetch('<?= BASE_URL ?>/api/usuarios.php', {
        method: data.id ? 'PUT' : 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    const json = await res.json();
    if (json.success) {
        Swal.fire({icon:'success', title:'Guardado', timer:1500, showConfirmButton:false});
        init();
    } else {
        Swal.fire({icon:'error', text: json.error});
    }
}
</script>
</body>
</html>
