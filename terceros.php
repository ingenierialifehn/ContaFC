<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requirePermiso('terceros');

$user    = Auth::user();
$empresa = null;
try {
    $db = Database::getInstance()->getPdo();
    $empresa = $db->prepare("SELECT * FROM empresas WHERE id = :id");
    $empresa->execute([':id' => Auth::empresaId()]);
    $empresa = $empresa->fetch();
} catch (\Throwable $e) {}

$activeNav = 'terceros';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-[#f8fafc]">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes/Proveedores RTN – <?= htmlspecialchars($empresa['nombre'] ?? 'ContaFC') ?></title>
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
    <style>
        .premium-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .premium-card:hover { transform: translateY(-4px); box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1); }
        .btn-honduras { background: linear-gradient(135deg, #0073cf 0%, #00569e 100%); }
    </style>
</head>
<body class="h-full font-sans flex text-sm overflow-hidden">

<?php require __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-1 flex flex-col min-w-0 bg-slate-50">
    <!-- Header Premium -->
    <header class="bg-white border-b border-slate-200 px-8 py-5 flex items-center justify-between z-10 shadow-sm">
        <div>
            <nav class="flex text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 gap-2">
                <span class="text-honduras">Honduras</span>
                <span>/</span>
                <span>Contabilidad</span>
            </nav>
            <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Clientes, Proveedores y RTN</h1>
            <p class="text-slate-500 text-xs mt-0.5">Gestión integral de terceros para cumplimiento legal hondureño.</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="abrirModalTercero()" 
                    class="btn-honduras px-6 py-3 text-white rounded-2xl hover:opacity-90 transition font-bold shadow-lg shadow-blue-500/20 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Registrar Nuevo
            </button>
        </div>
    </header>

    <div class="flex-1 overflow-auto p-8">
        <!-- Filtros -->
        <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3 flex-1 max-w-2xl">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input type="text" id="t-search" oninput="debounceSearch(this.value)" 
                           placeholder="Buscar por RTN, Nombre o Código..." 
                           class="w-full h-12 pl-12 pr-4 border border-slate-200 rounded-2xl outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras transition-all bg-white text-slate-700 shadow-sm">
                </div>
                <select id="t-tipo" onchange="cargarTerceros()" 
                        class="h-12 px-6 border border-slate-200 rounded-2xl outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras bg-white text-slate-600 font-medium shadow-sm cursor-pointer">
                    <option value="">Todos los registros</option>
                    <option value="cliente">Solo Clientes</option>
                    <option value="proveedor">Solo Proveedores</option>
                    <option value="empleado">Planilla / Empleados</option>
                </select>
            </div>
            
            <div class="text-slate-400 text-xs font-medium">
                Viendo <span id="t-count" class="text-slate-700 font-bold">0</span> registros encontrados
            </div>
        </div>

        <!-- Grid Container -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6" id="t-grid">
            <!-- Cargando Skeleton -->
            <?php for($i=0; $i<6; $i++): ?>
            <div class="bg-white p-6 rounded-3xl border border-slate-100 animate-pulse">
                <div class="flex gap-4 mb-4">
                    <div class="w-14 h-14 bg-slate-100 rounded-2xl"></div>
                    <div class="flex-1 py-1 space-y-2">
                        <div class="h-4 bg-slate-100 rounded w-3/4"></div>
                        <div class="h-3 bg-slate-100 rounded w-1/2"></div>
                    </div>
                </div>
                <div class="h-10 bg-slate-50 rounded-xl w-full mt-4"></div>
            </div>
            <?php endfor; ?>
        </div>
        
        <div id="t-empty" class="hidden py-32 text-center text-slate-400 bg-white rounded-3xl border-2 border-dashed border-slate-200">
            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <p class="text-xl font-bold text-slate-600">No hay coincidencias</p>
            <p class="max-w-xs mx-auto mt-2 italic text-sm text-slate-400">Prueba ajustando los filtros o verifica el RTN ingresado.</p>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let searchTimeout;
let tercerosCache = [];

document.addEventListener('DOMContentLoaded', cargarTerceros);

function debounceSearch(v) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(cargarTerceros, 300);
}

async function cargarTerceros() {
    const q    = document.getElementById('t-search').value;
    const tipo = document.getElementById('t-tipo').value;
    const grid = document.getElementById('t-grid');
    const empty= document.getElementById('t-empty');
    const count= document.getElementById('t-count');

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/terceros.php?q=${encodeURIComponent(q)}&tipo=${encodeURIComponent(tipo)}`);
        const json = await res.json();
        tercerosCache = json.data || [];
        
        let data = [...tercerosCache];
        // Client-side filtering as fallback or extra layer
        if (tipo) {
            data = data.filter(r => r.tipo_tercero && String(r.tipo_tercero).toLowerCase().includes(tipo.toLowerCase()));
        }

        count.innerText = data.length.toString();

        if (!data.length) {
            grid.innerHTML = '';
            empty.classList.remove('hidden');
            return;
        }

        empty.classList.add('hidden');
        grid.innerHTML = data.map(r => `
            <div class="premium-card bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm relative overflow-hidden flex flex-col justify-between">
                <div class="absolute top-0 right-0 p-5 flex gap-2">
                    <button onclick="abrirModalTercero(${r.id})" class="p-2 text-slate-400 hover:text-honduras hover:bg-blue-50 rounded-xl transition" title="Editar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </button>
                    <button onclick="borrarTercero(${r.id})" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition" title="Eliminar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>

                <div>
                    <div class="flex items-center gap-4 mb-5">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center text-slate-400 font-extrabold text-2xl border border-slate-200 uppercase shadow-inner">
                            ${(r.nombre || '?').charAt(0)}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-slate-800 text-base leading-tight truncate pr-16" title="${r.nombre}">${r.nombre}</h3>
                            <div class="flex items-center gap-1.5 mt-1">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">${r.tipo_documento}:</span>
                                <span class="text-xs text-honduras font-mono font-bold">${r.nit_cc || 'PENDIENTE'}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 mb-6">
                        ${(r.tipo_tercero || '').split(',').filter(t => t.trim() !== '').map(t => `
                            <span class="px-3 py-1 rounded-full text-[9px] font-bold uppercase tracking-wider ${tagColor(t)}">
                                ${t === 'cliente' ? 'Cliente' : t === 'proveedor' ? 'Proveedor' : t}
                            </span>
                        `).join('')}
                    </div>
                </div>

                <div class="pt-5 border-t border-slate-100 flex justify-between items-center mt-auto">
                    <div class="text-[10px] text-slate-400">
                        CÓDIGO: <span class="font-bold text-slate-600 font-mono">${r.codigo}</span>
                    </div>
                    <div class="flex items-center gap-1 text-[10px] text-slate-400 italic">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        ${r.ciudad || 'Honduras'}
                    </div>
                </div>
            </div>
        `).join('');
    } catch (err) {
        console.error(err);
        grid.innerHTML = '<div class="col-span-full py-20 text-center text-red-500">Error conectando con el servidor.</div>';
    }
}

async function abrirModalTercero(id = null) {
    let r = { id:null, codigo:'', razon_social:'', nit_cc:'', tipo_persona:'J', tipo_documento:'RTN', email:'', telefono:'', direccion:'', ciudad:'', tipo_tercero:'cliente', activo:1 };
    
    if (id) {
        Swal.fire({ title:'Abriendo ficha...', allowOutsideClick:false, didOpen:()=>Swal.showLoading()});
        const res = await fetch(`<?= BASE_URL ?>/api/terceros.php?id=${id}`);
        const json = await res.json();
        Swal.close();
        if (json.data) r = json.data;
    }

    const { value: formValues } = await Swal.fire({
        title: `<div class="text-xl font-bold pt-2">${id ? 'Editar Ficha' : 'Nuevo Registro'}</div>`,
        width: '700px',
        background: '#ffffff',
        padding: '1.5rem',
        html: `
            <div class="text-left space-y-6 pt-4 text-slate-700">
                <div class="grid grid-cols-2 gap-5">
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-400 uppercase ml-1">Código de Registro</label>
                        <input id="sw_codigo" value="${r.codigo}" placeholder="Ej: 001-HN" 
                               class="w-full h-12 border border-slate-200 rounded-2xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras transition-all text-sm font-mono">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-400 uppercase ml-1">Nombre Completo / Razón Social</label>
                        <input id="sw_nombre" value="${r.razon_social}" placeholder="Nombre de la empresa o persona" 
                               class="w-full h-12 border border-slate-200 rounded-2xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras transition-all text-sm font-semibold">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-5">
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-400 uppercase ml-1">Tipo de Persona</label>
                        <select id="sw_tp" class="w-full h-12 border border-slate-200 rounded-2xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras bg-white text-sm">
                            <option value="J" ${r.tipo_persona=='J'?'selected':''}>Jurídica (Empresa)</option>
                            <option value="N" ${r.tipo_persona=='N'?'selected':''}>Natural (Individuo)</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-400 uppercase ml-1">Tipo identificación</label>
                        <select id="sw_td" class="w-full h-12 border border-slate-200 rounded-2xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras bg-white text-sm">
                            <option value="RTN" ${r.tipo_documento=='RTN'?'selected':''}>RTN (Honduras)</option>
                            <option value="DNI" ${r.tipo_documento=='DNI'?'selected':''}>DNI (Identidad)</option>
                            <option value="PAS" ${r.tipo_documento=='PAS'?'selected':''}>Pasaporte</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-400 uppercase ml-1">Número de RTN / Doc</label>
                        <input id="sw_nit" value="${r.nit_cc}" placeholder="0801-..." 
                               class="w-full h-12 border border-slate-200 rounded-2xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras transition-all text-sm font-mono font-bold tracking-widest">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-400 uppercase ml-1">Correo Electrónico</label>
                        <input id="sw_email" type="email" value="${r.email||''}" placeholder="contacto@ejemplo.hn" 
                               class="w-full h-12 border border-slate-200 rounded-2xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras transition-all text-sm">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-400 uppercase ml-1">Teléfono</label>
                        <input id="sw_tel" value="${r.telefono||''}" placeholder="+504 ...." 
                               class="w-full h-12 border border-slate-200 rounded-2xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras transition-all text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-400 uppercase ml-1">Dirección Legal</label>
                        <input id="sw_dir" value="${r.direccion||''}" placeholder="Colonia, Calle..." 
                               class="w-full h-12 border border-slate-200 rounded-2xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras transition-all text-sm">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-400 uppercase ml-1">Ciudad / Municipio</label>
                        <input id="sw_ciu" value="${r.ciudad||''}" placeholder="Tegucigalpa, SPS..." 
                               class="w-full h-12 border border-slate-200 rounded-2xl px-4 outline-none focus:ring-2 focus:ring-honduras/20 focus:border-honduras transition-all text-sm">
                    </div>
                </div>

                <div class="bg-slate-50 p-6 rounded-[1.5rem] border border-slate-100">
                    <label class="text-[11px] font-bold text-slate-400 uppercase block mb-3">Vínculo con esta Empresa</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="bg-white p-3 border border-slate-200 rounded-xl flex items-center gap-3 cursor-pointer hover:border-honduras transition-colors">
                            <input type="checkbox" name="sw_tt" value="cliente" ${r.tipo_tercero.includes('cliente')?'checked':''} class="w-5 h-5 text-honduras rounded-lg">
                            <span class="text-sm font-bold text-slate-700">Es Cliente</span>
                        </label>
                        <label class="bg-white p-3 border border-slate-200 rounded-xl flex items-center gap-3 cursor-pointer hover:border-honduras transition-colors">
                            <input type="checkbox" name="sw_tt" value="proveedor" ${r.tipo_tercero.includes('proveedor')?'checked':''} class="w-5 h-5 text-honduras rounded-lg">
                            <span class="text-sm font-bold text-slate-700">Es Proveedor</span>
                        </label>
                        <label class="bg-white p-3 border border-slate-200 rounded-xl flex items-center gap-3 cursor-pointer hover:border-honduras transition-colors">
                            <input type="checkbox" name="sw_tt" value="empleado" ${r.tipo_tercero.includes('empleado')?'checked':''} class="w-5 h-5 text-honduras rounded-lg">
                            <span class="text-sm font-bold text-slate-700">Es Empleado</span>
                        </label>
                        <label class="bg-white p-3 border border-slate-200 rounded-xl flex items-center gap-3 cursor-pointer hover:border-honduras transition-colors">
                            <input type="checkbox" name="sw_tt" value="otro" ${r.tipo_tercero.includes('otro')?'checked':''} class="w-5 h-5 text-honduras rounded-lg">
                            <span class="text-sm font-bold text-slate-700">Otro / Varios</span>
                        </label>
                    </div>
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
            const types = Array.from(document.querySelectorAll('input[name="sw_tt"]:checked')).map(el => el.value);
            const data = {
                id: id,
                codigo: document.getElementById('sw_codigo').value,
                razon_social: document.getElementById('sw_nombre').value,
                nit_cc: document.getElementById('sw_nit').value,
                tipo_persona: document.getElementById('sw_tp').value,
                tipo_documento: document.getElementById('sw_td').value,
                email: document.getElementById('sw_email').value,
                telefono: document.getElementById('sw_tel').value,
                direccion: document.getElementById('sw_dir').value,
                ciudad: document.getElementById('sw_ciu').value,
                tipo_tercero: types,
                activo: 1
            };
            if (!data.codigo || !data.razon_social || !data.nit_cc) {
                Swal.showValidationMessage('Los campos con nombre, código y RTN son imperativos.');
                return false;
            }
            if (types.length === 0) {
                Swal.showValidationMessage('Seleccione al menos un tipo (Cliente/Proveedor)');
                return false;
            }
            return data;
        }
    });

    if (formValues) guardarTercero(formValues);
}

async function guardarTercero(data) {
    const res = await fetch('<?= BASE_URL ?>/api/terceros.php', {
        method: data.id ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    if (res.ok) {
        Swal.fire({ icon:'success', title:'Datos actualizados', text:'Cambios reflejados en el sistema legal.', timer:2000, showConfirmButton:false });
        cargarTerceros();
    } else {
        const err = await res.json();
        Swal.fire({ icon:'error', title:'Error Legal', text: err.error || 'No se pudo procesar la solicitud.' });
    }
}

async function borrarTercero(id) {
    const resConfirm = await Swal.fire({
        title: '¿Confirmar eliminación?',
        text: 'Solo podrá eliminar registros que no tengan movimientos contables registrados en Honduras.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Eliminar Registro',
        customClass: {
            confirmButton: 'px-6 py-2 bg-red-600 text-white rounded-xl font-bold ml-2',
            cancelButton: 'px-6 py-2 bg-slate-100 text-slate-500 rounded-xl font-bold'
        },
        buttonsStyling: false
    });

    if (resConfirm.isConfirmed) {
        const res = await fetch(`<?= BASE_URL ?>/api/terceros.php?id=${id}`, { method:'DELETE' });
        if (res.ok) {
            Swal.fire({ icon:'success', title:'Registro Eliminado', timer:1500, showConfirmButton:false });
            cargarTerceros();
        } else {
            const err = await res.json();
            Swal.fire({ icon:'error', title:'Restricción Contable', text: err.error || 'El tercero posee histórico y no puede borrarse.' });
        }
    }
}

function tagColor(tipo) {
    if (tipo === 'cliente') return 'bg-emerald-100/50 text-emerald-700 border border-emerald-200';
    if (tipo === 'proveedor') return 'bg-amber-100/50 text-amber-700 border border-amber-200';
    if (tipo === 'empleado') return 'bg-indigo-100/50 text-indigo-700 border border-indigo-200';
    return 'bg-slate-100 text-slate-600 border border-slate-200';
}
</script>

</body>
</html>
