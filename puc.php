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

$activeNav = 'puc';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ContaFC – Plan de Cuentas | <?= htmlspecialchars($empresa['nombre'] ?? '') ?></title>
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
            <h1 class="text-lg font-bold text-slate-800">Plan de Cuentas (PUC)</h1>
            <p class="text-xs text-slate-500">Mantenimiento de la estructura contable</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-2 border border-slate-300 text-slate-600 rounded-lg hover:bg-slate-50 transition text-xs font-semibold flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Exportar
            </button>
            <button onclick="abrirModalCuenta()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-xs font-bold shadow-lg shadow-blue-500/30">
                + Nueva Cuenta
            </button>
        </div>
    </header>

    <div class="p-6">
        <div class="mb-4">
            <input type="text" id="puc-search" oninput="filterPuc(this.value)" placeholder="Filtrar por código o nombre..." 
                   class="w-full max-w-md h-10 px-4 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500 transition-all shadow-sm bg-white">
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50 text-slate-400 text-[10px] uppercase font-bold tracking-widest border-b border-slate-100">
                        <th class="px-4 py-3 text-left w-32">CÓDIGO</th>
                        <th class="px-4 py-3 text-left">DESCRIPCIÓN DE LA CUENTA</th>
                        <th class="px-4 py-3 text-center w-20">NAT</th>
                        <th class="px-4 py-3 text-center w-24">TIPO</th>
                        <th class="px-4 py-3 text-center w-24">ACEPTA MOV</th>
                        <th class="px-4 py-3 text-center w-20">ESTADO</th>
                        <th class="px-4 py-3 text-right">ACCIONES</th>
                    </tr>
                </thead>
                <tbody id="puc-body">
                    <tr><td colspan="7" class="text-center py-20 text-slate-400 italic">Cargando estructura...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
let pucOriginal = [];

document.addEventListener('DOMContentLoaded', cargarPuc);

async function cargarPuc() {
    const res = await fetch('<?= BASE_URL ?>/api/puc.php');
    const json = await res.json();
    pucOriginal = json.data || [];
    renderPuc(pucOriginal);
}

function renderPuc(data) {
    const body = document.getElementById('puc-body');
    if (!data.length) {
        body.innerHTML = '<tr><td colspan="7" class="text-center py-20 text-slate-400">Sin registros.</td></tr>';
        return;
    }

    body.innerHTML = data.map(r => {
        const lvl = parseInt(r.nivel);
        const accepts = r.acepta_movimiento == 1;
        const nature = r.naturaleza === 'D' ? 'D' : 'C';
        const typeMap = { A:'Activo',P:'Pasivo',R:'Patrimonio',G:'Gasto/Costo',O:'Orden' };
        
        return `<tr class="border-b border-slate-100 hover:bg-slate-50 transition-all group">
            <td class="px-4 py-2 font-mono text-[13px] text-slate-600">${r.codigo}</td>
            <td class="px-4 py-2">
                <div style="padding-left: ${(lvl-1)*16}px" class="${lvl <= 2 ? 'font-bold' : ''}">
                    ${r.nombre}
                </div>
            </td>
            <td class="px-4 py-2 text-center text-xs text-slate-400">${nature}</td>
            <td class="px-4 py-2 text-center text-xs text-slate-400">${typeMap[r.tipo_cuenta]||r.tipo_cuenta}</td>
            <td class="px-4 py-2 text-center">
                ${accepts ? '<span class="text-emerald-500 font-bold">✓</span>' : '<span class="text-slate-200">—</span>'}
            </td>
            <td class="px-4 py-2 text-center text-[10px]">
                <span class="${r.activa == 1 ? 'text-green-600' : 'text-red-400'} font-bold">${r.activa == 1 ? 'ACT' : 'INA'}</span>
            </td>
            <td class="px-4 py-2 text-right">
                <div class="flex justify-end gap-1">
                    <button onclick="abrirModalCuenta(${r.id})" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded transition" title="Editar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </button>
                    ${!accepts ? `<button onclick="borrarCuenta(${r.id})" class="p-1.5 text-slate-300 hover:text-red-600 hover:bg-red-50 rounded transition" title="Eliminar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>` : `<button onclick="borrarCuenta(${r.id})" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded transition" title="Eliminar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>`}
                </div>
            </td>
        </tr>`;
    }).join('');
}

function filterPuc(q) {
    q = q.toLowerCase();
    const filtered = pucOriginal.filter(r => r.codigo.toLowerCase().includes(q) || r.nombre.toLowerCase().includes(q));
    renderPuc(filtered);
}

function abrirModalCuenta(id = null) {
    const r = id ? pucOriginal.find(x => x.id == id) : { id:null, codigo:'', nombre:'', nivel:1, codigo_padre:'', naturaleza:'D', tipo_cuenta:'A', acepta_movimiento:0, activa:1 };
    
    Swal.fire({
        title: id ? 'Editar Cuenta' : 'Nueva Cuenta Contable',
        width: '500px',
        html: `
            <div class="text-left space-y-4 pt-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Código</label>
                        <input id="sw_codigo" value="${r.codigo}" ${id?'disabled':''} class="w-full h-10 border rounded-xl px-3 outline-none focus:ring-2 focus:ring-blue-500 bg-white font-mono text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Nombre</label>
                        <input id="sw_nombre" value="${r.nombre}" class="w-full h-10 border rounded-xl px-3 outline-none focus:ring-2 focus:ring-blue-500 bg-white text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Cód. Padre</label>
                        <input id="sw_padre" value="${r.codigo_padre||''}" class="w-full h-10 border rounded-xl px-3 outline-none focus:ring-2 focus:ring-blue-500 bg-white font-mono text-sm" placeholder="Ej: 1101">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Nivel</label>
                        <select id="sw_nivel" class="w-full h-10 border rounded-xl px-3 outline-none focus:ring-2 focus:ring-blue-500 bg-white text-sm">
                            <option value="1" ${r.nivel==1?'selected':''}>1 (Clase)</option>
                            <option value="2" ${r.nivel==2?'selected':''}>2 (Grupo)</option>
                            <option value="3" ${r.nivel==3?'selected':''}>3 (Cuenta)</option>
                            <option value="4" ${r.nivel==4?'selected':''}>4 (Subcuenta)</option>
                            <option value="5" ${r.nivel==5?'selected':''}>5 (Auxiliar)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Naturaleza</label>
                        <select id="sw_nat" class="w-full h-10 border rounded-xl px-3 outline-none focus:ring-2 focus:ring-blue-500 bg-white text-sm">
                            <option value="D" ${r.naturaleza=='D'?'selected':''}>Débito (+)</option>
                            <option value="C" ${r.naturaleza=='C'?'selected':''}>Crédito (-)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Tipo Cuenta</label>
                        <select id="sw_tipo" class="w-full h-10 border rounded-xl px-3 outline-none focus:ring-2 focus:ring-blue-500 bg-white text-sm">
                            <option value="A" ${r.tipo_cuenta=='A'?'selected':''}>Activo</option>
                            <option value="P" ${r.tipo_cuenta=='P'?'selected':''}>Pasivo</option>
                            <option value="R" ${r.tipo_cuenta=='R'?'selected':''}>Patrimonio</option>
                            <option value="G" ${r.tipo_cuenta=='G'?'selected':''}>Gasto/Costo</option>
                            <option value="O" ${r.tipo_cuenta=='O'?'selected':''}>Orden</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-6 p-3 bg-slate-50 rounded-xl">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="sw_mov" ${r.acepta_movimiento==1?'checked':''} class="w-4 h-4 text-blue-600 rounded">
                        <span class="text-xs font-semibold text-slate-700">Acepta Movimiento</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="sw_activa" ${r.activa==1?'checked':''} class="w-4 h-4 text-blue-600 rounded">
                        <span class="text-xs font-semibold text-slate-700">Activa</span>
                    </label>
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'Guardar Cuenta',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#1e3a5f',
        preConfirm: () => {
            const data = {
                id: id,
                codigo: document.getElementById('sw_codigo').value,
                nombre: document.getElementById('sw_nombre').value,
                codigo_padre: document.getElementById('sw_padre').value,
                nivel: document.getElementById('sw_nivel').value,
                naturaleza: document.getElementById('sw_nat').value,
                tipo_cuenta: document.getElementById('sw_tipo').value,
                acepta_movimiento: document.getElementById('sw_mov').checked ? 1 : 0,
                activa: document.getElementById('sw_activa').checked ? 1 : 0
            };
            if (!data.codigo || !data.nombre) {
                Swal.showValidationMessage('El código y el nombre son obligatorios');
                return false;
            }
            return data;
        }
    }).then(result => {
        if (result.isConfirmed) guardarCuenta(result.value);
    });
}

async function guardarCuenta(data) {
    const res = await fetch('<?= BASE_URL ?>/api/puc.php', {
        method: data.id ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    const json = await res.json();
    if (res.ok) {
        Swal.fire({ icon:'success', title:'Guardado con éxito', timer:1500, showConfirmButton:false });
        cargarPuc();
    } else {
        Swal.fire({ icon:'error', title:'Error', text: json.error || 'No se pudo guardar' });
    }
}

async function borrarCuenta(id) {
    const confirms = await Swal.fire({
        title: '¿Eliminar cuenta?',
        text: 'Esta acción no se puede deshacer y solo funcionará si la cuenta no tiene movimientos.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'No, cancelar',
        confirmButtonColor: '#ef4444'
    });

    if (confirms.isConfirmed) {
        const res = await fetch(`<?= BASE_URL ?>/api/puc.php?id=${id}`, { method:'DELETE' });
        const json = await res.json();
        if (res.ok) {
            Swal.fire({ icon:'success', title:'Eliminado', timer:1500, showConfirmButton:false });
            cargarPuc();
        } else {
            Swal.fire({ icon:'error', title:'Error', text: json.error || 'No se pudo eliminar' });
        }
    }
}
</script>
</body>
</html>
