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
    $tipos_ret = $db->query("SELECT * FROM tipos_retencion WHERE activa = 1")->fetchAll();
} catch (\Throwable $e) {}

$activeNav = 'certificados';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50 text-slate-700">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificados de Retención SAR – <?= htmlspecialchars($empresa['nombre'] ?? 'ContaFC') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT:'#1e3a5f', light:'#2563eb', dark:'#0f1f3d' },
                        honduras: '#0369a1',
                        sar: '#0ea5e9',
                    },
                    fontFamily: { sans: ['Inter','system-ui','sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="h-full font-sans flex text-sm overflow-hidden text-slate-700">

<?php require __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-1 flex flex-col min-w-0 bg-slate-50">
    <!-- Header -->
    <header class="bg-white border-b border-slate-200 px-8 py-5 flex items-center justify-between z-10 shadow-sm">
        <div class="flex items-center gap-6">
            <div class="w-14 h-14 bg-sar/10 text-sar rounded-3xl flex items-center justify-center shadow-inner group">
                 <svg class="w-8 h-8 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <div>
                <nav class="flex text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 gap-2">
                    <span class="text-honduras">Cumplimiento Tributario</span>
                    <span>/</span>
                    <span>Honduras - SAR</span>
                </nav>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight leading-none">Certificados de Retención</h1>
                <p class="text-slate-500 text-xs mt-1">Generación y control de retenciones de ISV y Fuente.</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="abrirModalCertificado()" 
                    class="bg-sar px-6 py-3 text-white rounded-2xl hover:opacity-90 transition font-bold shadow-lg shadow-blue-500/20 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Emitir Certificado
            </button>
        </div>
    </header>

    <div class="flex-1 overflow-auto p-8">
        <div class="flex flex-col gap-8">
            
            <!-- Listado de certificados emitidos -->
            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl overflow-hidden">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-slate-400 text-[10px] uppercase font-bold tracking-widest border-b border-slate-100 bg-slate-50/50">
                            <th class="px-8 py-5">Correlativo / Fecha</th>
                            <th class="px-8 py-5">Proveedor / RTN</th>
                            <th class="px-8 py-5">Tipo de Retención / Base</th>
                            <th class="px-8 py-5 text-right font-black">Monto Retenido</th>
                            <th class="px-8 py-5 text-center">Formato</th>
                        </tr>
                    </thead>
                    <tbody id="certificados-body" class="divide-y divide-slate-50">
                         <tr><td colspan="5" class="text-center py-20 text-slate-400 italic">Cargando certificados emitidos...</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Widget Banner Honduras -->
            <div class="bg-gradient-to-r from-honduras/90 to-sar/80 p-10 rounded-[2.5rem] text-white flex items-center justify-between shadow-2xl">
                 <div class="flex items-center gap-6">
                    <div class="w-20 h-20 bg-white/10 backdrop-blur-md rounded-3xl flex items-center justify-center">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-2xl font-black mb-1">Declaraciones Juradas (SAR)</h4>
                        <p class="text-xs text-white/70 max-w-sm font-medium tracking-tight">Utilice este módulo para preparar sus reportes de compras y ventas de acuerdo al Régimen de Facturación SAR.</p>
                    </div>
                 </div>
                 <button class="px-8 py-4 bg-white text-honduras rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl">Generar Excel SAR</button>
            </div>
        </div>
    </div>
</main>

<script>
let certificados = [];

document.addEventListener('DOMContentLoaded', cargarCertificados);

async function cargarCertificados() {
    const res = await fetch('<?= BASE_URL ?>/api/certificados.php');
    const json = await res.json();
    certificados = json.data || [];
    renderCertificados();
}

function renderCertificados() {
    const body = document.getElementById('certificados-body');
    if (!certificados.length) {
        body.innerHTML = '<tr><td colspan="5" class="text-center py-24 text-slate-400 uppercase font-black tracking-widest italic opacity-60">No hay certificados emitidos en este periodo.</td></tr>';
        return;
    }

    body.innerHTML = certificados.map(c => `
        <tr class="hover:bg-slate-50/80 transition-all">
            <td class="px-8 py-5">
                <div class="flex flex-col">
                    <span class="font-black text-slate-800 tracking-tight text-base mb-0.5">N° ${c.correlativo}</span>
                    <span class="text-[10px] text-slate-400 font-black uppercase tracking-widest">${c.fecha}</span>
                </div>
            </td>
            <td class="px-8 py-5">
                <div class="flex flex-col">
                    <span class="font-bold text-slate-700 text-sm truncate max-w-[200px]">${c.tercero_nom}</span>
                    <span class="text-[10px] font-mono text-slate-400 font-black tracking-widest">${c.tercero_rtn}</span>
                </div>
            </td>
            <td class="px-8 py-5">
                <div class="flex flex-col">
                    <span class="font-black text-[10px] uppercase text-honduras tracking-widest">${c.ret_nombre}</span>
                    <span class="text-[11px] text-slate-400 font-bold italic">Base: ${fmtHNL(c.base_imponible)} (${Math.round(c.porcentaje)}%)</span>
                </div>
            </td>
            <td class="px-8 py-5 text-right font-black text-base text-rose-600">
                ${fmtHNL(c.monto_retencion)}
            </td>
            <td class="px-8 py-5 text-center">
                 <button onclick="imprimirCertificado(${c.id})" class="p-2.5 bg-white border border-slate-200 text-slate-400 hover:text-sar hover:border-sar rounded-xl shadow-sm transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-width="2"></path></svg>
                </button>
            </td>
        </tr>
    `).join('');
}

function abrirModalCertificado() {
    Swal.fire({
        title: 'Emitir Certificado de Retención',
        width: '700px',
        html: `
            <div class="text-left grid grid-cols-2 gap-6 p-4">
                <div class="col-span-2 space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Proveedor (RTN / Nombre)</label>
                    <input id="sw_tercero_search" placeholder="Buscar por RTN o Nombre..." class="w-full h-11 border border-slate-200 rounded-xl px-4 outline-none text-sm font-bold shadow-sm">
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Tipo de Retención</label>
                    <select id="sw_tipo_ret" class="w-full h-11 border border-slate-200 rounded-xl px-4 outline-none text-xs font-black bg-slate-50">
                        <?php foreach($tipos_ret as $tr): ?>
                        <option value="<?= $tr['id'] ?>" data-perc="<?= $tr['porcentaje'] ?>"><?= $tr['nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Fecha Emisión</label>
                    <input id="sw_fecha" type="date" value="<?= date('Y-m-d') ?>" class="w-full h-11 border border-slate-200 rounded-xl px-4 outline-none text-xs font-bold">
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Base Imponible (Lempiras)</label>
                    <input id="sw_base" type="number" step="0.01" value="0.00" class="w-full h-11 border border-slate-200 rounded-xl px-4 outline-none text-right font-mono font-black text-slate-800" oninput="calcRet()">
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Monto a Retener (Lempiras)</label>
                    <input id="sw_monto" type="number" step="0.01" class="w-full h-11 border border-rose-200 bg-rose-50 rounded-xl px-4 outline-none text-right font-mono font-black text-rose-600" readonly>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '✓ Generar e Imprimir',
        customClass: { confirmButton: 'bg-honduras text-white px-8 py-3.5 rounded-2xl font-bold ml-2 shadow-lg', cancelButton: 'bg-slate-100 text-slate-500 px-8 py-3.5 rounded-2xl font-bold' },
        buttonsStyling: false,
        preConfirm: async () => {
             const r = await fetch(`<?= BASE_URL ?>/api/terceros.php?q=` + document.getElementById('sw_tercero_search').value);
             const j = await r.json();
             const ter = j.data?.[0];
             if(!ter) { Swal.showValidationMessage('Proveedor no identificado.'); return false; }
             
             return {
                 tercero_id: ter.id,
                 tipo_retencion_id: document.getElementById('sw_tipo_ret').value,
                 fecha: document.getElementById('sw_fecha').value,
                 base_imponible: document.getElementById('sw_base').value,
                 porcentaje: document.querySelector('#sw_tipo_ret option:checked').dataset.perc,
                 monto_retencion: document.getElementById('sw_monto').value,
                 empresa_id: <?= Auth::empresaId() ?>
             };
        }
    }).then(async result => {
        if(result.isConfirmed) {
            const res = await fetch('<?= BASE_URL ?>/api/certificados.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(result.value)
            });
            if(res.ok) {
                Swal.fire({ icon:'success', title:'Certificado Emitido', timer:1500, showConfirmButton:false });
                cargarCertificados();
            }
        }
    });
}

function calcRet() {
    const base = parseFloat(document.getElementById('sw_base').value) || 0;
    const perc = parseFloat(document.querySelector('#sw_tipo_ret option:checked').dataset.perc) || 0;
    document.getElementById('sw_monto').value = (base * (perc / 100)).toFixed(2);
}

function fmtHNL(v) {
    return new Intl.NumberFormat('es-HN', { style:'currency', currency:'HNL' }).format(v||0);
}
</script>
</body>
</html>
