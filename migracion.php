<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requireRol('admin');

$empresaActiva = null;
try {
    $db = Database::getInstance()->getPdo();
    $empresaActiva = $db->query("SELECT * FROM empresas WHERE id = " . Auth::empresaId())->fetch();
} catch (\Throwable $e) {}

$activeNav = 'migracion';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migración Legacy GDB | ContaFC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT:'#1e3a5f', light:'#2563eb', dark:'#0f1f3d' },
                        firebird: '#e53e3e',
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
                <span class="text-firebird">Legacy</span>
                <span>/</span>
                <span>Firebird GDB</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight leading-none">Importador WXManager Antiguo</h1>
            <p class="text-slate-500 text-xs mt-1">Sincronización de saldos, terceros y cuentas desde archivos .GDB.</p>
        </div>
        <div class="flex items-center gap-2 px-4 py-2 bg-blue-50 border border-blue-100 rounded-2xl">
            <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold text-xs">
                <?= htmlspecialchars(substr($empresaActiva['nombre'] ?? 'E', 0, 1)) ?>
            </div>
            <div>
                <p class="text-[10px] font-bold text-blue-500 uppercase tracking-tighter">Destino de datos</p>
                <p class="text-xs font-black text-blue-900"><?= htmlspecialchars($empresaActiva['nombre'] ?? 'Empresa no seleccionada') ?></p>
            </div>
        </div>
    </header>

    <div class="flex-1 overflow-auto p-8 flex flex-col items-center justify-center">
        <div class="max-w-2xl w-full">
            <!-- Card Principal -->
            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-2xl p-10 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-10 opacity-5 pointer-events-none">
                    <svg class="w-40 h-40" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L1 21h22L12 2zm0 3.99L18.53 17H5.47L12 5.99zM11 14v2h2v-2h-2zm0-6v4h2V8h-2z"/></svg>
                </div>

                <div class="text-center mb-10">
                    <div class="w-20 h-20 bg-rose-50 text-rose-600 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-sm border border-rose-100 rotate-3">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                    </div>
                    <h2 class="text-3xl font-black text-slate-800 tracking-tight">Convertir Archivo .GDB</h2>
                    <p class="text-slate-500 mt-2">Sube tu base de datos Firebird (Firebird 1.5 o WXManager) para iniciar el mapeo automático a MySQL Honda.</p>
                </div>

                <!-- Dropzone de Archivo -->
                <div id="drop-zone" class="border-2 border-dashed border-slate-200 rounded-[2rem] p-12 text-center hover:border-honduras transition-all cursor-pointer bg-slate-50 group">
                    <input type="file" id="gdb-file" class="hidden" accept=".gdb">
                    <div class="mb-4 text-slate-300 group-hover:text-honduras transition-colors">
                        <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    </div>
                    <p class="font-bold text-slate-600">Haz clic o arrastra el archivo CLINICA_FARMACIA.GDB</p>
                    <p class="text-xs text-slate-400 mt-1 uppercase tracking-widest font-bold">Máximo recomendado: 500MB</p>
                </div>

                <div id="file-info" class="hidden mt-6 p-4 bg-emerald-50 rounded-2xl border border-emerald-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="text-emerald-600">
                             <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        </div>
                        <span id="file-name" class="text-sm font-bold text-emerald-800">archivo.gdb</span>
                    </div>
                    <button onclick="cancelarUpload()" class="text-xs font-bold text-emerald-600 hover:underline">Cambiar</button>
                </div>

                <div class="mt-10">
                    <button id="btn-migrar" onclick="iniciarMigracion()" disabled
                            class="w-full h-16 rounded-2xl bg-slate-200 text-slate-400 font-black text-lg transition-all shadow-xl shadow-slate-200/50 flex items-center justify-center gap-2">
                        Iniciar Proceso de Migración
                    </button>
                </div>

                <div class="mt-8 flex gap-3 text-[10px] text-slate-400 font-bold uppercase tracking-tighter opacity-60">
                    <div class="flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        Mapeo de PUC
                    </div>
                    <div class="flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        Terceros RTN
                    </div>
                    <div class="flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        Saldos Iniciales
                    </div>
                </div>
            </div>

            <div class="mt-8 p-6 bg-blue-50/50 rounded-3xl border border-blue-100 flex gap-4">
                <div class="w-10 h-10 rounded-xl bg-blue-100/50 text-blue-500 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                   <p class="text-[11px] text-blue-600/70 font-bold uppercase tracking-widest mb-1">Nota Técnica</p>
                   <p class="text-xs text-blue-800 leading-relaxed font-medium">Este proceso sobrescribirá el PUC y Terceros de la empresa actual si existen conflictos. Se recomienda hacer un backup antes de proceder.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
const dropZone = document.getElementById('drop-zone');
const fileInput = document.getElementById('gdb-file');
const btnMigrar = document.getElementById('btn-migrar');
const fileInfo = document.getElementById('file-info');

dropZone.onclick = () => fileInput.click();

fileInput.onchange = (e) => {
    const file = e.target.files[0];
    if (file) {
        mostrarArchivo(file);
    }
};

function mostrarArchivo(file) {
    document.getElementById('file-name').innerText = `${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
    dropZone.classList.add('hidden');
    fileInfo.classList.remove('hidden');
    btnMigrar.disabled = false;
    btnMigrar.classList.replace('bg-slate-200', 'bg-firebird');
    btnMigrar.classList.replace('text-slate-400', 'text-white');
}

function cancelarUpload() {
    fileInput.value = '';
    dropZone.classList.remove('hidden');
    fileInfo.classList.add('hidden');
    btnMigrar.disabled = true;
    btnMigrar.classList.replace('bg-firebird', 'bg-slate-200');
    btnMigrar.classList.replace('text-white', 'text-slate-400');
}

async function iniciarMigracion() {
    const file = fileInput.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    Swal.fire({
        title: 'Procesando GDB...',
        html: `<div class="mt-4">
                  <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                      <div class="h-full bg-firebird animate-[progress_2s_ease-in-out_infinite]" style="width: 30%"></div>
                  </div>
                  <p class="text-xs text-slate-400 mt-4 italic">Analizando tablas: TERCEROS, CUENTAS, SALDOS...</p>
               </div>`,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const res = await fetch('<?= BASE_URL ?>/api/migracion.php', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();
        
        if (res.ok) {
            Swal.fire({
                icon: 'success',
                title: 'Migración Completada',
                text: json.message || 'Los datos han sido mapeados correctamente.',
                confirmButtonText: 'Ver Resultados',
                confirmButtonColor: '#0073cf'
            }).then(() => {
                window.location.href = 'terceros.php';
            });
        } else {
            throw new Error(json.error || 'Fallo en la migración');
        }
    } catch (err) {
        Swal.fire({ icon:'error', title:'Error de Conversión', text: err.message });
    }
}
</script>

<style>
@keyframes progress {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(300%); }
}
</style>

</body>
</html>
