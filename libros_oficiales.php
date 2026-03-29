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
    $periodos = $db->query("SELECT * FROM periodos WHERE empresa_id = " . Auth::empresaId() . " ORDER BY anio DESC, mes DESC")->fetchAll();
} catch (\Throwable $e) {}

$activeNav = 'libros';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50 text-slate-700">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libros Oficiales – <?= htmlspecialchars($empresa['nombre'] ?? 'ContaFC') ?></title>
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
            <div class="w-14 h-14 bg-slate-900 border-b-4 border-honduras text-white rounded-3xl flex items-center justify-center shadow-inner group">
                 <svg class="w-8 h-8 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
            <div>
                <nav class="flex text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 gap-2">
                    <span class="text-honduras">Auditoría & Libros</span>
                    <span>/</span>
                    <span>Honduras 2026</span>
                </nav>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight leading-none">Libros Oficiales Autorizados</h1>
                <p class="text-slate-500 text-xs mt-1">Generación de archivos para impresión en hojas foliadas.</p>
            </div>
        </div>
    </header>

    <div class="flex-1 overflow-auto p-8">
        <div class="max-w-6xl mx-auto flex flex-col gap-10">
             <!-- Control de Libros -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                
                <!-- Libro Diario -->
                <div class="bg-white rounded-[2.5rem] border border-slate-200 p-10 shadow-sm hover:shadow-2xl transition group relative overflow-hidden">
                    <div class="absolute -right-8 -top-8 w-32 h-32 bg-sky-50 rounded-full blur-2xl group-hover:bg-sky-100 transition duration-500"></div>
                    <h3 class="text-2xl font-black text-slate-800 mb-2 relative">Libro Diario</h3>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-6 border-b border-slate-50 pb-4">Asientos de Diario Cronológicos</p>
                    
                    <div class="space-y-4">
                         <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Período Fiscal</label>
                            <select id="p_diario" class="w-full h-11 border border-slate-200 rounded-2xl px-4 outline-none text-xs font-black shadow-sm">
                                <?php foreach($periodos as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= $p['mes'] ?> / <?= $p['anio'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Folio Inicial</label>
                            <input id="f_diario" type="number" value="1" class="w-full h-11 border border-slate-200 rounded-2xl px-4 outline-none text-xs font-black shadow-sm">
                        </div>
                        <button onclick="generarLibro('DIARIO')" class="w-full py-4 mt-6 bg-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-honduras transition shadow-lg shadow-black/10">Generar Libro Diario</button>
                    </div>
                </div>

                <!-- Libro Mayor -->
                <div class="bg-white rounded-[2.5rem] border border-slate-200 p-10 shadow-sm hover:shadow-2xl transition group relative overflow-hidden">
                    <div class="absolute -right-8 -top-8 w-32 h-32 bg-emerald-50 rounded-full blur-2xl group-hover:bg-emerald-100 transition duration-500"></div>
                    <h3 class="text-2xl font-black text-slate-800 mb-2 relative">Libro Mayor</h3>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-6 border-b border-slate-50 pb-4">Saldos y Acumulados Mensuales</p>
                    
                    <div class="space-y-4">
                         <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Período Fiscal</label>
                            <select id="p_mayor" class="w-full h-11 border border-slate-200 rounded-2xl px-4 outline-none text-xs font-black shadow-sm">
                                <?php foreach($periodos as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= $p['mes'] ?> / <?= $p['anio'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Folio Inicial</label>
                            <input id="f_mayor" type="number" value="1" class="w-full h-11 border border-slate-200 rounded-2xl px-4 outline-none text-xs font-black shadow-sm">
                        </div>
                        <button onclick="generarLibro('MAYOR')" class="w-full py-4 mt-6 bg-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-emerald-600 transition shadow-lg shadow-black/10">Generar Libro Mayor</button>
                    </div>
                </div>

                <!-- Inventarios y Balances -->
                <div class="bg-white rounded-[2.5rem] border border-slate-200 p-10 shadow-sm hover:shadow-2xl transition group relative overflow-hidden">
                    <div class="absolute -right-8 -top-8 w-32 h-32 bg-rose-50 rounded-full blur-2xl group-hover:bg-rose-100 transition duration-500"></div>
                    <h3 class="text-2xl font-black text-slate-800 mb-2 relative">Iny. & Balances</h3>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-6 border-b border-slate-50 pb-4">Situación Financiera Anual</p>
                    
                    <div class="space-y-4">
                         <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Periodo de Balances</label>
                            <select id="p_inv" class="w-full h-11 border border-slate-200 rounded-2xl px-4 outline-none text-xs font-black shadow-sm">
                                <?php 
                                    $prevYear = null;
                                    foreach($periodos as $p): 
                                        if($p['anio'] !== $prevYear):
                                ?>
                                <option value="<?= $p['id'] ?>">Balance Anual: <?= $p['anio'] ?></option>
                                <?php 
                                        $prevYear = $p['anio'];
                                        endif;
                                    endforeach; 
                                ?>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Folio Inicial</label>
                            <input id="f_inv" type="number" value="1" class="w-full h-11 border border-slate-200 rounded-2xl px-4 outline-none text-xs font-black shadow-sm">
                        </div>
                        <button onclick="generarLibro('INVENTARIOS')" class="w-full py-4 mt-6 bg-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-rose-600 transition shadow-lg shadow-black/10">Generar Inventarios</button>
                    </div>
                </div>

            </div>

             <!-- Banner Legal -->
            <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-12 rounded-[2.5rem] border-l-[10px] border-honduras shadow-2xl text-white">
                <div class="flex items-center gap-10">
                    <div class="w-24 h-24 bg-white/5 rounded-3xl flex items-center justify-center border border-white/5">
                        <svg class="w-12 h-12 text-honduras" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                    </div>
                    <div>
                        <h4 class="text-3xl font-black mb-2 italic tracking-tighter uppercase">Cumplimiento SAR / Ley del Comerciante</h4>
                        <p class="text-sm text-slate-400 max-w-2xl font-medium leading-relaxed">Los Libros Oficiales deben imprimirse mensualmente o anualmente en hojas autorizadas y foliadas. El sistema garantiza la secuencia numérica y los totales cruzados entre Diario y Mayor para evitar sanciones administrativas.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function generarLibro(tipo) {
    const idMap = { 'DIARIO': 'p_diario', 'MAYOR': 'p_mayor', 'INVENTARIOS': 'p_inv' };
    const folMap = { 'DIARIO': 'f_diario', 'MAYOR': 'f_mayor', 'INVENTARIOS': 'f_inv' };
    
    const pId = document.getElementById(idMap[tipo]).value;
    const folio = document.getElementById(folMap[tipo]).value;

    Swal.fire({
        title: `Generando Libro ${tipo}`,
        html: `<div class='p-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest'>Procesando periodo contable y calculando saldos acumulados...</div>`,
        timer: 1500,
        didOpen: () => Swal.showLoading(),
        willClose: () => {
             // Abrir visor de libros (simulado via API/XLS por ahora)
             window.open(`<?= BASE_URL ?>/api/libros_oficiales.php?tipo=${tipo}&pid=${pId}&folio=${folio}`);
        }
    });
}
</script>

</body>
</html>
