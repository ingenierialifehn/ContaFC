<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requirePermiso('puc');

$user    = Auth::user();
$empresa = null;
try {
    $db = Database::getInstance()->getPdo();
    $empresa = $db->query("SELECT * FROM empresas WHERE id = " . Auth::empresaId())->fetch();
} catch (\Throwable $e) {}

$activeNav = 'proyectos';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50 text-slate-700">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Proyectos – <?= htmlspecialchars($empresa['nombre'] ?? 'ContaFC') ?></title>
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

<main class="flex-1 flex flex-col min-w-0 bg-slate-50">
    <!-- Header -->
    <header class="bg-white border-b border-slate-200 px-8 py-5 flex items-center justify-between z-10 shadow-sm">
        <div>
            <nav class="flex text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 gap-2">
                <span class="text-sky-500">Administración</span>
                <span>/</span>
                <span>Contabilidad Core</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight leading-none">Gestión de Proyectos</h1>
            <p class="text-slate-500 text-xs mt-1">Control de seguimiento financiero por proyectos específicos.</p>
        </div>
        <button onclick="abrirModalProyecto()" 
                class="bg-sky-500 px-6 py-3 text-white rounded-2xl hover:bg-sky-600 transition font-bold shadow-lg shadow-sky-500/20 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nuevo Proyecto
        </button>
    </header>

    <div class="flex-1 overflow-auto p-8 no-scrollbar">
        <div class="w-full">
            <div class="bg-white rounded-[2rem] border border-slate-200 shadow-xl overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-slate-400 text-[10px] uppercase font-bold tracking-widest border-b border-slate-100 bg-slate-50/50">
                            <th class="px-8 py-5">Código</th>
                            <th class="px-8 py-5">Nombre del Proyecto</th>
                            <th class="px-8 py-5 text-center">Estado</th>
                            <th class="px-8 py-5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="proyecto-body" class="divide-y divide-slate-50">
                        <tr><td colspan="4" class="text-center py-20 text-slate-400 italic font-medium">Cargando proyectos...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
let proyectos = [];
document.addEventListener('DOMContentLoaded', cargarProyectos);

async function cargarProyectos() {
    try {
        const res = await fetch('<?= BASE_URL ?>/api/proyectos.php');
        const json = await res.json();
        proyectos = json.data || [];
        renderProyectos();
    } catch (e) {
        console.error(e);
        document.getElementById('proyecto-body').innerHTML = '<tr><td colspan="4" class="text-center py-20 text-rose-500 font-bold">Error al cargar datos.</td></tr>';
    }
}

function renderProyectos() {
    const body = document.getElementById('proyecto-body');
    if (!proyectos.length) {
        body.innerHTML = '<tr><td colspan="4" class="text-center py-20 text-slate-400 font-medium">No hay proyectos registrados aún.</td></tr>';
        return;
    }

    body.innerHTML = proyectos.map(p => `
        <tr class="hover:bg-slate-50/80 transition-all group">
            <td class="px-8 py-5">
                <span class="font-mono font-bold text-sky-600 bg-sky-50 px-3 py-1.5 rounded-xl border border-sky-100">${p.codigo}</span>
            </td>
            <td class="px-8 py-5 flex items-center gap-3">
                <div class="w-10 h-10 bg-slate-50 rounded-xl flex items-center justify-center border border-slate-100 text-slate-400 font-bold text-sm uppercase overflow-hidden shrink-0">
                    ${p.logo_path ? `<img src="<?= BASE_URL ?>/${p.logo_path}" class="w-full h-full object-contain" />` : p.nombre.charAt(0)}
                </div>
                <span class="font-bold text-slate-800 text-base tracking-tight">${p.nombre}</span>
            </td>
            <td class="px-8 py-5 text-center">
                <span class="px-3 py-1.5 rounded-full text-[10px] font-black tracking-widest ${p.activo == 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}">
                    ${p.activo == 1 ? 'ACTIVO' : 'INACTIVO'}
                </span>
            </td>
            <td class="px-8 py-5 text-right">
                <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button onclick="abrirModalProyecto(${p.id})" class="p-2.5 bg-white border border-slate-200 text-slate-400 hover:text-sky-500 hover:border-sky-500 rounded-xl shadow-sm transition-all hover:scale-110">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </button>
                    <button onclick="borrarProyecto(${p.id})" class="p-2.5 bg-white border border-slate-200 text-slate-400 hover:text-rose-500 hover:border-rose-500 rounded-xl shadow-sm transition-all hover:scale-110">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function abrirModalProyecto(id = null) {
    const p = id ? proyectos.find(x => x.id == id) : { id:null, codigo:'', nombre:'', activo:1, logo_path:'' };
    
    Swal.fire({
        title: id ? 'Editar Proyecto' : 'Nuevo Proyecto',
        width: '500px',
        background: '#ffffff',
        html: `
            <div class="text-left space-y-4 pt-4">
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Código Identificador</label>
                    <input id="sw_codigo" value="${p.codigo}" ${id?'disabled':''} placeholder="Ej: PROY-001" 
                           class="w-full h-12 border border-slate-200 rounded-2xl px-5 outline-none focus:ring-4 focus:ring-sky-500/10 focus:border-sky-500 text-sm font-mono font-bold tracking-widest transition-all">
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Nombre Completo del Proyecto</label>
                    <input id="sw_nombre" value="${p.nombre}" placeholder="Ej: Construcción de Edificio Norte" 
                           class="w-full h-12 border border-slate-200 rounded-2xl px-5 outline-none focus:ring-4 focus:ring-sky-500/10 focus:border-sky-500 text-sm font-bold transition-all">
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Logo del Proyecto</label>
                    <div class="flex items-center gap-4 p-4 bg-slate-50 border border-slate-100 rounded-2xl">
                        <div id="sw_logo_preview" class="w-16 h-16 bg-white rounded-xl border-dashed border-2 border-slate-200 flex items-center justify-center overflow-hidden shrink-0">
                            ${p.logo_path ? `<img src="<?= BASE_URL ?>/${p.logo_path}" class="w-full h-full object-contain" />` : '<span class="text-[10px] text-slate-400">Sin logo</span>'}
                        </div>
                        <div class="flex-1">
                            <input type="file" id="sw_logo_file" accept="image/*" class="hidden" onchange="subirLogo(this)">
                            <button type="button" onclick="document.getElementById('sw_logo_file').click()" class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-100 transition shadow-sm">Seleccionar Logo</button>
                            <input type="hidden" id="sw_logo_path" value="${p.logo_path || ''}">
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-4 bg-slate-50 border border-slate-100 rounded-2xl cursor-pointer hover:bg-slate-100 transition-colors" onclick="document.getElementById('sw_activo').click()">
                    <input type="checkbox" id="sw_activo" ${p.activo==1 ? 'checked' : ''} class="w-5 h-5 text-sky-500 rounded-lg border-slate-300 focus:ring-sky-500">
                    <div class="flex flex-col">
                        <span class="text-xs font-black text-slate-700 uppercase tracking-tight font-sans">Proyecto Activo</span>
                        <span class="text-[10px] text-slate-400 font-bold">Permite vincular movimientos contables</span>
                    </div>
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: id ? 'Actualizar Datos' : 'Registrar Proyecto',
        cancelButtonText: 'Cancelar',
        buttonsStyling: false,
        customClass: {
            confirmButton: 'px-8 py-4 bg-sky-500 text-white rounded-2xl font-black text-[12px] uppercase tracking-widest ml-3 shadow-xl shadow-sky-500/20 hover:scale-105 transition-transform',
            cancelButton: 'px-8 py-4 bg-slate-100 text-slate-500 rounded-2xl font-black text-[12px] uppercase tracking-widest hover:bg-slate-200 transition-colors'
        },
        preConfirm: () => {
             const data = {
                 id: id,
                 codigo: document.getElementById('sw_codigo').value.trim(),
                 nombre: document.getElementById('sw_nombre').value.trim(),
                 activo: document.getElementById('sw_activo').checked ? 1 : 0,
                 logo_path: document.getElementById('sw_logo_path').value
             };
             if (!data.codigo || !data.nombre) {
                 Swal.showValidationMessage('Por favor completa todos los campos');
                 return false;
             }
             return data;
        }
    }).then(result => {
        if (result.isConfirmed) guardarProyecto(result.value);
    });
}

async function subirLogo(input) {
    if (!input.files || !input.files[0]) return;
    
    const formData = new FormData();
    formData.append('logo', input.files[0]);

    Swal.showLoading();
    try {
        const res = await fetch('<?= BASE_URL ?>/api/upload_logo.php', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();
        if (json.success) {
            document.getElementById('sw_logo_path').value = json.path;
            document.getElementById('sw_logo_preview').innerHTML = `<img src="<?= BASE_URL ?>/${json.path}" class="w-full h-full object-contain" />`;
            Swal.hideLoading();
        } else {
            Swal.fire('Error', json.error || 'No se pudo subir el logo', 'error');
        }
    } catch (err) {
        Swal.fire('Error', 'Error de conexión', 'error');
    }
}

async function guardarProyecto(data) {
    try {
        const res = await fetch('<?= BASE_URL ?>/api/proyectos.php', {
            method: data.id ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        
        if (res.ok && json.success) {
            Swal.fire({ 
                icon:'success', 
                title:'¡Éxito!', 
                text: 'El proyecto se ha guardado correctamente.',
                timer:1800, 
                showConfirmButton:false,
                background: '#ffffff',
                customClass: { title: 'font-black text-slate-800', htmlContainer: 'font-medium text-slate-500' }
            });
            cargarProyectos();
        } else {
            throw new Error(json.error || 'Error al procesar la solicitud');
        }
    } catch (e) {
        Swal.fire({ icon:'error', title:'Error', text: e.message, background: '#ffffff' });
    }
}

async function borrarProyecto(id) {
    const res = await Swal.fire({
        title: '¿Confirmar eliminación?',
        text: 'Esta acción no se puede deshacer si el proyecto es eliminado permanentemente.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'No, cancelar',
        buttonsStyling: false,
        background: '#ffffff',
        customClass: { 
            confirmButton: 'bg-rose-500 text-white px-8 py-4 rounded-2xl ml-3 font-black text-[12px] uppercase tracking-widest shadow-xl shadow-rose-500/20 hover:scale-105 transition-transform', 
            cancelButton: 'bg-slate-100 text-slate-500 px-8 py-4 rounded-2xl font-black text-[12px] uppercase tracking-widest hover:bg-slate-200 transition-colors' 
        }
    });

    if (res.isConfirmed) {
        try {
            const delRes = await fetch(`<?= BASE_URL ?>/api/proyectos.php?id=${id}`, { method:'DELETE' });
            const json = await delRes.json();
            
            if (delRes.ok && json.success) {
                Swal.fire({ icon:'success', title:'Eliminado', text:'El proyecto ha sido removido.', timer:1500, showConfirmButton:false });
                cargarProyectos();
            } else {
                throw new Error(json.error || 'No se pudo eliminar el proyecto');
            }
        } catch (e) {
            Swal.fire({ icon:'error', title:'Restricción de Borrado', text: e.message, background: '#ffffff' });
        }
    }
}
</script>
</body>
</html>
