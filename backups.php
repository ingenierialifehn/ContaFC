<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requireRol('admin');

$user    = Auth::user();
$empresa = null;
try {
    $db = Database::getInstance()->getPdo();
    $empresa = $db->query("SELECT * FROM empresas WHERE id = " . Auth::empresaId())->fetch();
} catch (\Throwable $e) {}

$activeNav = 'backups';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respaldo de Datos | ContaFC</title>
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
        <div>
            <nav class="flex text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 gap-2">
                <span class="text-honduras">Seguridad</span>
                <span>/</span>
                <span>Infraestructura</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight leading-none">Gestión de Backups SQL</h1>
            <p class="text-slate-500 text-xs mt-1">Generación y descarga de respaldos para la base de datos MySQL.</p>
        </div>
        <button onclick="generarBackup()" 
                class="bg-honduras px-6 py-3 text-white rounded-2xl hover:opacity-90 transition font-bold shadow-lg shadow-blue-500/20 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Generar Respaldo Ahora
        </button>
    </header>

    <div class="flex-1 overflow-auto p-8">
        <div class="w-full">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Backup Settings -->
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Configuración Automática</span>
                    </div>
                    <div class="p-8 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Frecuencia</label>
                                <select id="set_frecuencia" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-honduras/20 outline-none transition">
                                    <option value="desactivado">Desactivado</option>
                                    <option value="diaria">Diaria</option>
                                    <option value="semanal">Semanal</option>
                                    <option value="mensual">Mensual</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Hora Ejecución</label>
                                <input type="time" id="set_hora" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-honduras/20 outline-none transition">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Notificar al Correo (Opcional)</label>
                            <input type="email" id="set_email" placeholder="ejemplo@correo.com" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-honduras/20 outline-none transition uppercase">
                        </div>
                        <button onclick="guardarConfiguracion()" class="w-full bg-slate-800 text-white py-4 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-black transition shadow-lg shadow-black/10">
                            Guardar Configuración
                        </button>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="p-8 bg-honduras/5 rounded-3xl border border-honduras/10 flex flex-col justify-center">
                    <div class="w-14 h-14 rounded-2xl bg-honduras text-white flex items-center justify-center mb-6 shadow-xl shadow-honduras/20">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <h3 class="text-xl font-black text-slate-800 tracking-tight mb-2">Backups Inteligentes</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">ContaFC puede generar copias de seguridad de forma silenciosa. Una vez configurada la frecuencia, el sistema mantendrá sus datos a salvo sin intervención manual.</p>
                </div>
            </div>

            <div class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Historial de Respaldos</span>
                    <button onclick="cargarRespaldos()" class="text-honduras hover:underline font-bold text-xs flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Refrescar
                    </button>
                </div>
                
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-slate-400 text-[10px] uppercase font-bold tracking-widest border-b border-slate-100 bg-white">
                            <th class="px-8 py-5">Nombre del Archivo</th>
                            <th class="px-8 py-5 text-center">Tamaño</th>
                            <th class="px-8 py-5 text-center">Fecha de Creación</th>
                            <th class="px-8 py-5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="backups-body" class="divide-y divide-slate-50">
                        <!-- Loaded via JS -->
                    </tbody>
                </table>
                
                <div id="loading-state" class="py-20 text-center text-slate-400 hidden">
                    <svg class="animate-spin w-8 h-8 mx-auto mb-4 text-slate-300" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <p class="font-medium">Consultando registros...</p>
                </div>

                <div id="empty-state" class="py-20 text-center text-slate-400 hidden">
                    <p class="font-bold text-slate-600">No hay respaldos generados todavía.</p>
                </div>
            </div>
            
            <div class="mt-8 p-6 bg-amber-50 rounded-3xl border border-amber-100 flex gap-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <h3 class="font-bold text-amber-800 tracking-tight">Regla de Seguridad</h3>
                    <p class="text-xs text-amber-700/80 leading-relaxed mt-1">Los respaldos SQL se almacenan localmente en el servidor. Recomendamos descargarlos y guardarlos en una ubicación externa (Google Drive, NAS, etc.) periódicamente.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    cargarRespaldos();
    cargarConfiguracion();
});

async function cargarConfiguracion() {
    try {
        const res = await fetch('<?= BASE_URL ?>/api/backups.php?settings=1');
        const json = await res.json();
        if (json.data) {
            document.getElementById('set_frecuencia').value = json.data.frecuencia || 'desactivado';
            document.getElementById('set_hora').value = json.data.hora || '00:00';
            document.getElementById('set_email').value = json.data.notificar_email || '';
        }
    } catch (err) { console.error('Error al cargar config:', err); }
}

async function guardarConfiguracion() {
    const data = {
        frecuencia: document.getElementById('set_frecuencia').value,
        hora: document.getElementById('set_hora').value,
        notificar_email: document.getElementById('set_email').value
    };

    try {
        const res = await fetch('<?= BASE_URL ?>/api/backups.php?settings=1', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        if (res.ok) {
            Swal.fire({ icon:'success', title:'Configuración Guardada', timer:1500, showConfirmButton:false });
        } else {
            throw new Error(json.error || 'No se pudo guardar');
        }
    } catch (err) {
        Swal.fire({ icon:'error', title:'Error', text: err.message });
    }
}

async function cargarRespaldos() {
    const list = document.getElementById('backups-body');
    const load = document.getElementById('loading-state');
    const empty= document.getElementById('empty-state');
    
    list.innerHTML = '';
    load.classList.remove('hidden');
    empty.classList.add('hidden');

    try {
        const res = await fetch('<?= BASE_URL ?>/api/backups.php');
        const json = await res.json();
        const data = json.data || [];
        
        load.classList.add('hidden');
        if (data.length === 0) {
            empty.classList.remove('hidden');
            return;
        }

        list.innerHTML = data.map(b => `
            <tr class="hover:bg-slate-50 transition-all group">
                <td class="px-8 py-5">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-400 flex items-center justify-center text-xs">
                           <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/></svg>
                        </div>
                        <span class="font-bold text-slate-700 text-sm font-mono">${b.name}</span>
                    </div>
                </td>
                <td class="px-8 py-5 text-center text-slate-500 font-medium text-xs">${b.size}</td>
                <td class="px-8 py-5 text-center text-slate-400 text-xs">${b.date}</td>
                <td class="px-8 py-5 text-right">
                    <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <a href="<?= BASE_URL ?>/api/backups.php?download=${b.name}" 
                           class="p-2 bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white rounded-xl transition" title="Descargar">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        </a>
                        <button onclick="borrarBackup('${b.name}')" 
                                class="p-2 bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white rounded-xl transition" title="Eliminar">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    } catch (err) {
        load.classList.add('hidden');
        Swal.fire({ icon:'error', title:'Error de comunicación', text: err.message });
    }
}

async function generarBackup() {
    Swal.fire({
        title: 'Generando Respaldo...',
        text: 'Por favor, no cierres esta ventana mientras realizamos el volcado de datos SQL.',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const res = await fetch('<?= BASE_URL ?>/api/backups.php', { method: 'POST' });
        const json = await res.json();
        if (res.ok) {
            Swal.fire({ icon:'success', title:'Backup generado con éxito', timer:2000, showConfirmButton:false });
            cargarRespaldos();
        } else {
            throw new Error(json.error || 'No se pudo generar');
        }
    } catch (err) {
        Swal.fire({ icon:'error', title:'Error de Sistema', text: err.message });
    }
}

async function borrarBackup(name) {
    const confirm = await Swal.fire({
        title: '¿Confirmar borrado?',
        text: 'Este archivo se eliminará permanentemente del servidor.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, borrar definitivamente',
        cancelButtonText: 'No, conservar',
        customClass: { confirmButton: 'bg-rose-600 text-white font-bold px-6 py-2 rounded-xl ml-2', cancelButton: 'bg-slate-100 text-slate-500 px-6 py-2 rounded-xl font-bold' },
        buttonsStyling: false
    });

    if (confirm.isConfirmed) {
        const res = await fetch('<?= BASE_URL ?>/api/backups.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ filename: name })
        });
        if (res.ok) {
            Swal.fire({ icon:'success', title:'Archivo eliminado', timer:1500, showConfirmButton:false });
            cargarRespaldos();
        }
    }
}
</script>
</body>
</html>
