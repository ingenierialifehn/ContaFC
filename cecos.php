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

$activeNav = 'cecos';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50 text-slate-700">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centros de Costo – <?= htmlspecialchars($empresa['nombre'] ?? 'ContaFC') ?></title>
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
            <nav class="flex text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 gap-2">
                <span class="text-honduras">Catálogos</span>
                <span>/</span>
                <span>Administrativo</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight leading-none">Centros de Costo</h1>
            <p class="text-slate-500 text-xs mt-1">Estructura de departamentos y proyectos para contabilidad administrativa.</p>
        </div>
        <button onclick="abrirModalCECO()" 
                class="bg-honduras px-6 py-3 text-white rounded-2xl hover:opacity-90 transition font-bold shadow-lg shadow-blue-500/20 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nuevo Centro
        </button>
    </header>

    <div class="flex-1 overflow-auto p-8">
        <div class="w-full">
            <div class="bg-white rounded-[2rem] border border-slate-200 shadow-xl overflow-hidden">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-slate-400 text-[10px] uppercase font-bold tracking-widest border-b border-slate-100 bg-slate-50/50">
                            <th class="px-8 py-5">Código CECO</th>
                            <th class="px-8 py-5">Descripción del Departamento / Proyecto</th>
                            <th class="px-8 py-5 text-center">Estado</th>
                            <th class="px-8 py-5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="ceco-body" class="divide-y divide-slate-50">
                        <tr><td colspan="4" class="text-center py-20 text-slate-400 italic">Cargando centros...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
let cecos = [];
document.addEventListener('DOMContentLoaded', cargarCECOS);

async function cargarCECOS() {
    const res = await fetch('<?= BASE_URL ?>/api/cecos.php');
    const json = await res.json();
    cecos = json.data || [];
    renderCECOS();
}

function renderCECOS() {
    const body = document.getElementById('ceco-body');
    if (!cecos.length) {
        body.innerHTML = '<tr><td colspan="4" class="text-center py-20 text-slate-400">No hay centros de costo registrados.</td></tr>';
        return;
    }

    body.innerHTML = cecos.map(c => `
        <tr class="hover:bg-slate-50 transition-all group">
            <td class="px-8 py-5">
                <span class="font-mono font-bold text-slate-700 bg-slate-100 px-3 py-1 rounded-lg border border-slate-200">${c.codigo}</span>
            </td>
            <td class="px-8 py-5">
                <span class="font-bold text-slate-800 text-base tracking-tight">${c.nombre}</span>
            </td>
            <td class="px-8 py-5 text-center">
                <span class="px-3 py-1 rounded-full text-[10px] font-black tracking-widest ${c.activa == 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}">
                    ${c.activa == 1 ? 'ACTIVO' : 'INACTIVO'}
                </span>
            </td>
            <td class="px-8 py-5 text-right">
                <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button onclick="abrirModalCECO(${c.id})" class="p-2.5 bg-white border border-slate-200 text-slate-400 hover:text-honduras hover:border-honduras rounded-xl shadow-sm transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </button>
                    <button onclick="borrarCECO(${c.id})" class="p-2.5 bg-white border border-slate-200 text-slate-400 hover:text-red-500 hover:border-red-500 rounded-xl shadow-sm transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function abrirModalCECO(id = null) {
    const c = id ? cecos.find(x => x.id == id) : { id:null, codigo:'', nombre:'', activa:1 };
    
    Swal.fire({
        title: id ? 'Editar Centro de Costo' : 'Nuevo Centro de Costo',
        width: '500px',
        html: `
            <div class="text-left space-y-4 pt-4">
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Código del CECO</label>
                    <input id="sw_codigo" value="${c.codigo}" ${id?'disabled':''} placeholder="Ej: ADM-01" 
                           class="w-full h-12 border border-slate-200 rounded-2xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras text-sm font-mono font-bold tracking-widest">
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Descripción / Departamento</label>
                    <input id="sw_nombre" value="${c.nombre}" placeholder="Ej: Gastos de Administración Central" 
                           class="w-full h-12 border border-slate-200 rounded-2xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras text-sm font-bold">
                </div>
                <div class="flex items-center gap-2 p-3 bg-slate-50 border border-slate-100 rounded-2xl">
                    <input type="checkbox" id="sw_activa" ${c.activa==1 ? 'checked' : ''} class="w-5 h-5 text-honduras rounded border-slate-300">
                    <label for="sw_activa" class="text-xs font-bold text-slate-700 cursor-pointer uppercase tracking-tighter">Este centro está activo para movimientos</label>
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'Guardar cambios',
        cancelButtonText: 'Cancelar',
        buttonsStyling: false,
        customClass: {
            confirmButton: 'px-8 py-3.5 bg-honduras text-white rounded-2xl font-bold ml-3 shadow-lg shadow-blue-500/20',
            cancelButton: 'px-8 py-3.5 bg-slate-100 text-slate-500 rounded-2xl font-bold'
        },
        preConfirm: () => {
             const data = {
                 id: id,
                 codigo: document.getElementById('sw_codigo').value,
                 nombre: document.getElementById('sw_nombre').value,
                 activa: document.getElementById('sw_activa').checked ? 1 : 0
             };
             if (!data.codigo || !data.nombre) {
                 Swal.showValidationMessage('Ambos campos son obligatorios');
                 return false;
             }
             return data;
        }
    }).then(result => {
        if (result.isConfirmed) guardarCECO(result.value);
    });
}

async function guardarCECO(data) {
    const res = await fetch('<?= BASE_URL ?>/api/cecos.php', {
        method: data.id ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    if (res.ok) {
        Swal.fire({ icon:'success', title:'Guardado exitoso', timer:1500, showConfirmButton:false });
        cargarCECOS();
    }
}

async function borrarCECO(id) {
    const res = await Swal.fire({
        title: '¿Confirmar borrado?',
        text: 'Se eliminará permanentemente si no tiene movimientos vinculados.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Eliminar',
        buttonsStyling: false,
        customClass: { confirmButton: 'bg-rose-600 text-white px-6 py-2 rounded-xl ml-2 font-bold', cancelButton: 'bg-slate-100 text-slate-500 px-6 py-2 rounded-xl font-bold' }
    });
    if (res.isConfirmed) {
        const delRes = await fetch(`<?= BASE_URL ?>/api/cecos.php?id=${id}`, { method:'DELETE' });
        if (delRes.ok) cargarCECOS();
    }
}
</script>
</body>
</html>
