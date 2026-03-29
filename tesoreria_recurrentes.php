<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();

$user    = Auth::user();
$empresa = null;
$periodos = [];
try {
    $db = Database::getInstance()->getPdo();
    $empresa = $db->query("SELECT * FROM empresas WHERE id = " . Auth::empresaId())->fetch();
    $periodos = $db->query("SELECT * FROM periodos WHERE empresa_id = " . Auth::empresaId() . " AND estado = 'abierto' ORDER BY anio DESC, mes DESC")->fetchAll();
} catch (\Throwable $e) {}

$activeNav = 'tesoreria';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50 text-slate-700">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recurrencia – <?= htmlspecialchars($empresa['nombre'] ?? 'ContaFC') ?></title>
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
<body class="h-full font-sans flex text-sm overflow-hidden text-slate-700">

<?php require __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-1 flex flex-col min-w-0 bg-slate-50">
    <!-- Header -->
    <header class="bg-white border-b border-slate-200 px-8 py-5 flex items-center justify-between z-10 shadow-sm">
        <div class="flex items-center gap-6">
            <div class="w-14 h-14 bg-slate-100 text-slate-800 rounded-3xl flex items-center justify-center shadow-inner">
                 <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <nav class="flex text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 gap-2">
                    <span class="text-honduras">Gestión de Tesorería</span>
                    <span>/</span>
                    <span>Automatizaciones</span>
                </nav>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight leading-none">Comprobantes Recurrentes</h1>
                <p class="text-slate-500 text-xs mt-1">Configuración de plantillas para gastos fijos y asientos automáticos.</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
             <button onclick="procesarLote()" 
                    class="bg-slate-800 px-6 py-3 text-white rounded-2xl hover:bg-slate-900 transition font-bold shadow-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Ejecutar Recurrencia
            </button>
            <button onclick="abrirModalRecurrente()" 
                    class="bg-honduras px-6 py-3 text-white rounded-2xl hover:opacity-90 transition font-bold shadow-lg shadow-blue-500/20 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nueva Plantilla
            </button>
        </div>
    </header>

    <div class="flex-1 overflow-auto p-8">
        <div class="max-w-5xl mx-auto">
            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl overflow-hidden">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-slate-400 text-[10px] uppercase font-bold tracking-widest border-b border-slate-100 bg-slate-50/50">
                            <th class="px-8 py-5">Nombre de la Plantilla</th>
                            <th class="px-8 py-5">Frecuencia</th>
                            <th class="px-8 py-5 text-center">Día Sugerido</th>
                            <th class="px-8 py-5 text-right">Último Proceso</th>
                            <th class="px-8 py-5 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="recurrentes-body" class="divide-y divide-slate-50">
                        <tr><td colspan="5" class="text-center py-20 text-slate-400 italic">Cargando plantillas recurrentes...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
let recurrentes = [];

document.addEventListener('DOMContentLoaded', cargarRecurrentes);

async function cargarRecurrentes() {
    const res = await fetch('<?= BASE_URL ?>/api/tesoreria.php?action=recurrentes');
    const json = await res.json();
    recurrentes = json.data || [];
    renderRecurrentes();
}

function renderRecurrentes() {
    const body = document.getElementById('recurrentes-body');
    if (!recurrentes.length) {
        body.innerHTML = '<tr><td colspan="5" class="text-center py-20 text-slate-400">No hay plantillas recurrentes pre-configuradas.</td></tr>';
        return;
    }

    body.innerHTML = recurrentes.map(r => `
        <tr class="hover:bg-slate-50 transition-all group">
            <td class="px-8 py-5">
                <span class="font-bold text-slate-800 text-base tracking-tight">${r.nombre}</span>
            </td>
            <td class="px-8 py-5">
                <span class="px-3 py-1 rounded-full text-[10px] font-black tracking-widest bg-blue-100 text-honduras uppercase">
                    ${r.frecuencia}
                </span>
            </td>
            <td class="px-8 py-5 text-center font-black text-slate-700">Día ${r.dia_ejecucion}</td>
            <td class="px-8 py-5 text-right font-mono text-xs text-slate-400">${r.ultimo_procesado || 'NUNCA'}</td>
            <td class="px-8 py-5 text-center">
                 <button onclick="borrarRecurrente(${r.id})" class="text-slate-300 hover:text-red-500 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
            </td>
        </tr>
    `).join('');
}

function procesarLote() {
    const per = <?= json_encode($periodos) ?>;
    if(!per.length) return Swal.fire({ icon:'warning', title:'Sin períodos abiertos' });

    Swal.fire({
        title: 'Ejecutar Recurrencia Mensual',
        html: `
            <div class="text-left p-4">
                <p class="text-[10px] items-center gap-1 font-bold text-slate-400 uppercase tracking-widest mb-4">Seleccionar Período Contable Destino</p>
                <select id="p_periodo" class="w-full h-12 border border-slate-200 rounded-2xl px-4 outline-none text-sm font-bold shadow-sm">
                    ${per.map(p => `<option value="${p.id}">${p.mes}/${p.anio}</option>`).join('')}
                </select>
                <div class="mt-6 p-4 bg-blue-50/50 rounded-2xl border border-blue-100 italic text-[11px] text-blue-700">Este proceso generará los comprobantes contables en estado 'REGISTRADO' basados en las plantillas seleccionadas.</div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: '✓ Ejecutar Proceso',
        buttonsStyling: false,
        customClass: { confirmButton: 'bg-honduras text-white px-8 py-3.5 rounded-2xl font-bold ml-2 shadow-lg shadow-blue-500/20', cancelButton: 'bg-slate-100 text-slate-500 px-8 py-3.5 rounded-2xl font-bold' },
    }).then(async result => {
        if(result.isConfirmed) {
            Swal.fire({ title:'Procesando...', didOpen: () => Swal.showLoading() });
            const res = await fetch('<?= BASE_URL ?>/api/tesoreria.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'process_recurrentes', periodo_id: document.getElementById('p_periodo').value })
            });
            const j = await res.json();
            if(res.ok) {
                Swal.fire({ icon:'success', title:'Proceso Finalizado', text: `Se ejecutaron exitosamente ${j.count} plantillas de recurrencia.` });
                cargarRecurrentes();
            }
        }
    });
}

function abrirModalRecurrente() {
    Swal.fire({
        title: 'Nueva Plantilla Recurrente',
        text: 'Las plantillas permiten auto-generar asientos complejos como nómina, alquileres o servicios fijos.',
        icon: 'info',
        confirmButtonText: 'Entendido, crear ahora',
        footer: '<span class="text-[10px] text-slate-400 uppercase font-black">Nota: La configuración completa requiere mapeo de líneas contables JSON.</span>'
    });
}
</script>
</body>
</html>
