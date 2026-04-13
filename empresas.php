<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
$user    = Auth::user();
$empresaActualId = Auth::empresaId();

$activeNav = 'empresas';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ContaFC – Gestión de Empresas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT:'#1e3a5f', light:'#2563eb', dark:'#0f1f3d' },
                    },
                    fontFamily: { sans: ['Inter','system-ui','sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="h-full font-sans flex text-sm">

<?php require __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-1 overflow-auto flex flex-col">
    <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between sticky top-0 z-10">
        <div>
            <h1 class="text-lg font-bold text-slate-800">Mis Empresas</h1>
            <p class="text-xs text-slate-500">Seleccione o administre sus compañías</p>
        </div>
        <?php if ($user['rol'] === 'admin'): ?>
        <button onclick="abrirModalEmpresa()" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition font-bold shadow-lg shadow-blue-500/20 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Nueva Empresa
        </button>
        <?php endif; ?>
    </header>

    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="emp-grid">
            <!-- Cargando... -->
        </div>
    </div>
</main>

<script>
let empresasCache = [];
const isAdmin = <?= $user['rol'] === 'admin' ? 'true' : 'false' ?>;
const empresaActualId = <?= $empresaActualId ?>;
const baseUrl = '<?= BASE_URL ?>';

document.addEventListener('DOMContentLoaded', cargarEmpresas);

function resolveMediaUrl(path) {
    if (!path) return '';
    if (/^(https?:)?\/\//i.test(path) || path.startsWith('data:')) return path;
    return `${baseUrl}/${String(path).replace(/^\/+/, '')}`;
}

async function cargarEmpresas() {
    const grid = document.getElementById('emp-grid');
    grid.innerHTML = '<div class="col-span-full py-20 text-center text-slate-400">Cargando empresas...</div>';

    const res = await fetch('<?= BASE_URL ?>/api/empresas.php');
    const json = await res.json();
    empresasCache = json.data || [];

    if (!empresasCache.length) {
        grid.innerHTML = '<div class="col-span-full py-20 text-center text-slate-400 italic">No hay empresas asignadas.</div>';
        return;
    }

    grid.innerHTML = empresasCache.map(e => {
        const isSelected = e.id == empresaActualId;
        const logoUrl = resolveMediaUrl(e.logo_path);
        const logoMarkup = logoUrl
            ? `<img src="${logoUrl}" alt="Logo de ${e.nombre}" class="w-14 h-14 rounded-2xl object-cover border border-slate-200 bg-white">`
            : `<div class="w-14 h-14 bg-slate-50 rounded-2xl flex items-center justify-center border border-slate-100 text-slate-400 font-bold text-xl uppercase">${e.nombre.charAt(0)}</div>`;
        return `
        <div class="bg-white rounded-2xl border ${isSelected ? 'border-blue-500 ring-2 ring-blue-500/10' : 'border-slate-200'} shadow-sm p-6 flex flex-col transition hover:shadow-md relative overflow-hidden">
            ${isSelected ? `
            <div class="absolute top-0 right-0 p-3">
                <span class="bg-blue-100 text-blue-700 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-tighter">Seleccionada</span>
            </div>` : ''}
            
            <div class="flex items-center gap-4 mb-5">
                ${logoMarkup}
                <div>
                    <h3 class="font-bold text-slate-800 text-base leading-tight">${e.nombre}</h3>
                    <p class="text-[11px] text-slate-400 font-mono mt-1">RTN: ${e.nit || '—'}</p>
                </div>
            </div>

            <div class="space-y-2 mb-6">
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span>${e.ciudad || '—'}, ${e.departamento || '—'}</span>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span>Moneda: <b>${e.moneda_base}</b></span>
                </div>
            </div>

            <div class="mt-auto flex flex-col gap-2">
                ${!isSelected ? `
                <button onclick="seleccionarEmpresa(${e.id})" class="w-full py-2 bg-slate-800 text-white rounded-lg font-bold text-xs hover:bg-black transition">
                    Administrar Contabilidad
                </button>` : `
                <button disabled class="w-full py-2 bg-blue-50 text-blue-600 rounded-lg font-bold text-xs opacity-80 cursor-default">
                    Actualmente activa
                </button>
                `}
                
                ${isAdmin ? `
                <div class="flex gap-2">
                    <button onclick="abrirModalEmpresa(${e.id})" class="flex-1 py-2 border border-slate-200 text-slate-600 rounded-lg font-bold text-[10px] hover:bg-slate-50 transition uppercase">Editar</button>
                    ${e.id != 1 ? `<button onclick="borrarEmpresa(${e.id})" class="flex-1 py-2 border border-red-100 text-red-500 rounded-lg font-bold text-[10px] hover:bg-red-50 transition uppercase">Eliminar</button>` : ''}
                </div>` : ''}
            </div>
        </div>`;
    }).join('');
}

async function seleccionarEmpresa(id) {
    Swal.fire({ title:'Cambiando empresa...', allowOutsideClick:false, didOpen:()=>Swal.showLoading()});
    const res = await fetch('<?= BASE_URL ?>/api/empresas.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'select', id: id })
    });
    if (res.ok) {
        window.location.href = '<?= BASE_URL ?>/dashboard.php';
    } else {
        Swal.fire('Error', 'No se pudo cambiar de empresa', 'error');
    }
}

async function abrirModalEmpresa(id = null) {
    let e = { id:null, codigo:'', nombre:'', nit:'', direccion:'', telefono:'', ciudad:'', departamento:'', moneda_base:'HNL', activa:1, logo_path:null };
    
    if (id) {
        e = empresasCache.find(x => x.id == id);
    }
    const logoUrl = resolveMediaUrl(e.logo_path);

    const { value: formValues } = await Swal.fire({
        title: id ? 'Editar Empresa' : 'Nueva Empresa',
        width: '600px',
        html: `
            <div class="text-left space-y-4 pt-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Código ID</label>
                        <input id="sw_codigo" value="${e.codigo}" class="w-full h-10 border rounded-xl px-3 outline-none focus:ring-2 focus:ring-blue-500 bg-white text-sm font-mono" placeholder="Ej: EMP_02">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Nombre Comercial</label>
                        <input id="sw_nombre" value="${e.nombre}" class="w-full h-10 border rounded-xl px-3 outline-none focus:ring-2 focus:ring-blue-500 bg-white text-sm">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">RTN / NIT</label>
                        <input id="sw_nit" value="${e.nit}" class="w-full h-10 border rounded-xl px-3 outline-none focus:ring-2 focus:ring-blue-500 bg-white text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Teléfono</label>
                        <input id="sw_tel" value="${e.telefono||''}" class="w-full h-10 border rounded-xl px-3 outline-none focus:ring-2 focus:ring-blue-500 bg-white text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Dirección</label>
                    <input id="sw_dir" value="${e.direccion||''}" class="w-full h-10 border rounded-xl px-3 outline-none focus:ring-2 focus:ring-blue-500 bg-white text-sm">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Ciudad</label>
                        <input id="sw_ciu" value="${e.ciudad||''}" class="w-full h-10 border rounded-xl px-3 outline-none focus:ring-2 focus:ring-blue-500 bg-white text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Departamento</label>
                        <input id="sw_dep" value="${e.departamento||''}" class="w-full h-10 border rounded-xl px-3 outline-none focus:ring-2 focus:ring-blue-500 bg-white text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Logo de la Empresa (Opcional)</label>
                    <div class="flex items-center gap-4">
                        ${logoUrl ? \`<img src="\${logoUrl}" class="w-12 h-12 rounded-lg object-cover border">\` : ''}
                        <input type="file" id="sw_logo" accept="image/*" class="text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    ${logoUrl ? \`<label class="flex items-center gap-2 mt-2 cursor-pointer text-xs text-red-500"><input type="checkbox" id="sw_remove_logo" value="1"> Eliminar logo actual</label>\` : ''}
                </div>
                ${!id ? '<div class="p-3 bg-blue-50 rounded-lg text-[10px] text-blue-700 font-medium">✨ Se inicializará automáticamente con el PUC de Honduras.</div>' : ''}
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'Guardar Empresa',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#1e3a5f',
        preConfirm: () => {
            return {
                id: id,
                codigo: document.getElementById('sw_codigo').value,
                nombre: document.getElementById('sw_nombre').value,
                nit: document.getElementById('sw_nit').value,
                telefono: document.getElementById('sw_tel').value,
                direccion: document.getElementById('sw_dir').value,
                ciudad: document.getElementById('sw_ciu').value,
                departamento: document.getElementById('sw_dep').value,
                logo: document.getElementById('sw_logo').files[0],
                remove_logo: document.getElementById('sw_remove_logo') ? (document.getElementById('sw_remove_logo').checked ? 1 : 0) : 0,
                moneda_base: 'HNL',
                activa: 1
            };
        }
    });

    if (formValues) guardarEmpresa(formValues);
}

async function guardarEmpresa(data) {
    const formData = new FormData();
    for (const key in data) {
        if (data[key] !== null && data[key] !== undefined) {
            formData.append(key, data[key]);
        }
    }
    if (data.id) {
        formData.append('_method', 'PUT');
    }
    const res = await fetch('<?= BASE_URL ?>/api/empresas.php', {
        method: 'POST',
        body: formData
    });
    const json = await res.json();
    if (res.ok) {
        Swal.fire({ icon:'success', title:'Guardado con éxito', timer:1500, showConfirmButton:false });
        cargarEmpresas();
    } else {
        Swal.fire({ icon:'error', title:'Error', text: json.error || 'No se pudo guardar' });
    }
}

async function borrarEmpresa(id) {
    const result = await Swal.fire({
        title: '¿Eliminar empresa?',
        text: '¡Atención! Se perderán todos los datos vinculados a esta empresa (cuentas, terceros, asientos...).',
        icon: 'warning',
        showCancelButton:true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Sí, eliminar todo'
    });
    if (result.isConfirmed) {
        const res = await fetch(`<?= BASE_URL ?>/api/empresas.php?id=${id}`, { method:'DELETE' });
        if (res.ok) {
            Swal.fire('Eliminado', 'La empresa ha sido eliminada', 'success');
            cargarEmpresas();
        } else {
            const json = await res.json();
            Swal.fire('Error', json.error || 'No se pudo eliminar', 'error');
        }
    }
}
</script>
</body>
</html>
