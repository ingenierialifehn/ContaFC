<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;
Auth::requireAuth();

function tableHasColumn(\PDO $db, string $table, string $column): bool
{
    static $cache = [];

    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $stmt = $db->prepare(
        "SELECT 1
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table
           AND COLUMN_NAME = :column
         LIMIT 1"
    );
    $stmt->execute([
        ':table' => $table,
        ':column' => $column,
    ]);

    $cache[$key] = (bool)$stmt->fetchColumn();
    return $cache[$key];
}

$user    = Auth::user();
$empresa = null;
$periodos = [];
$balanceYears = [];
try {
    $db = Database::getInstance()->getPdo();
    $eid = Auth::empresaId();
    $currentYear = (int) date('Y');
    $asientosTieneFecha = tableHasColumn($db, 'asientos', 'fecha');
    $empresa = $db->query("SELECT * FROM empresas WHERE id = $eid")->fetch();
    
    // --- AUTODETECCIÓN POR COMPROBANTES ---
    // Si hay comprobantes en años que no están en la tabla de periodos, los creamos
    $sqlAuto = "SELECT DISTINCT YEAR(fecha) as anio, MONTH(fecha) as mes 
                FROM comprobantes 
                WHERE empresa_id = :eid1 
                AND NOT EXISTS (
                    SELECT 1 FROM periodos 
                    WHERE empresa_id = :eid2 
                    AND anio = YEAR(comprobantes.fecha) 
                    AND mes = MONTH(comprobantes.fecha)
                )";
    $stmtAuto = $db->prepare($sqlAuto);
    $stmtAuto->execute([':eid1' => $eid, ':eid2' => $eid]);
    $missingPeriods = $stmtAuto->fetchAll();

    if (!empty($missingPeriods)) {
        $ins = $db->prepare("INSERT INTO periodos (empresa_id, anio, mes, estado) VALUES (:eid, :anio, :mes, 'abierto')");
        foreach ($missingPeriods as $m) {
            $ins->execute([':eid' => $eid, ':anio' => $m['anio'], ':mes' => $m['mes']]);
        }
        
        // Sincronizar period_id en comprobantes para asegurar consistencia
        $db->prepare("UPDATE comprobantes c 
                     JOIN periodos p ON p.empresa_id = c.empresa_id AND p.anio = YEAR(c.fecha) AND p.mes = MONTH(c.fecha)
                     SET c.periodo_id = p.id
                     WHERE c.empresa_id = :eid")->execute([':eid' => $eid]);
    }
    
    // Consulta final: Traemos todos los periodos registrados para esta empresa
    $sql = "SELECT id, anio, mes FROM periodos WHERE empresa_id = :eid ORDER BY anio DESC, mes DESC";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([':eid' => $eid]);
    $periodos = $stmt->fetchAll();

    if (empty($periodos)) {
        $periodos = [['id' => 0, 'anio' => date('Y'), 'mes' => date('m')]];
    }

    $yearsSqlParts = [
        "SELECT DISTINCT anio FROM periodos WHERE empresa_id = :eid_periodos AND anio <= :current_year_periodos",
        "SELECT DISTINCT YEAR(fecha) as anio FROM comprobantes WHERE empresa_id = :eid_comprobantes AND YEAR(fecha) <= :current_year_comprobantes",
    ];
    if ($asientosTieneFecha) {
        $yearsSqlParts[] = "SELECT DISTINCT YEAR(fecha) as anio FROM asientos WHERE empresa_id = :eid_asientos AND fecha IS NOT NULL AND YEAR(fecha) <= :current_year_asientos";
    }

    $stmtYears = $db->prepare(
        "SELECT MIN(anio) AS min_anio
         FROM (
             " . implode("\nUNION\n", $yearsSqlParts) . "
         ) years_source"
    );
    $stmtYears->bindValue(':eid_periodos', $eid, \PDO::PARAM_INT);
    $stmtYears->bindValue(':current_year_periodos', $currentYear, \PDO::PARAM_INT);
    $stmtYears->bindValue(':eid_comprobantes', $eid, \PDO::PARAM_INT);
    $stmtYears->bindValue(':current_year_comprobantes', $currentYear, \PDO::PARAM_INT);
    if ($asientosTieneFecha) {
        $stmtYears->bindValue(':eid_asientos', $eid, \PDO::PARAM_INT);
        $stmtYears->bindValue(':current_year_asientos', $currentYear, \PDO::PARAM_INT);
    }
    $stmtYears->execute();
    $minAnio = (int) ($stmtYears->fetchColumn() ?: $currentYear);

    for ($anio = $currentYear; $anio >= $minAnio; $anio--) {
        $balanceYears[] = $anio;
    }

    $latestYearWithData = (int) ($db->query("SELECT MAX(YEAR(fecha)) FROM comprobantes WHERE empresa_id = $eid AND estado = 'registrado'")->fetchColumn() ?: $currentYear);
} catch (\Throwable $e) {
    die("Error crítico de sistema: " . $e->getMessage());
}

$activeNav = 'libros';
$maxAnio = !empty($periodos) ? $periodos[0]['anio'] : date('Y');
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
                    <span>Honduras <?= $maxAnio ?></span>
                </nav>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight leading-none">Libros Oficiales Autorizados</h1>
                <p class="text-slate-500 text-xs mt-1">Generación de archivos para impresión en hojas foliadas.</p>
            </div>
        </div>
    </header>

    <div class="flex-1 overflow-auto p-8">
        <div class="w-full flex flex-col gap-10">
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
                        <div class="flex gap-2 mt-6">
                            <button onclick="generarLibro('DIARIO')" class="flex-1 py-4 bg-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-honduras transition shadow-lg shadow-black/10">Generar Libro Diario</button>
                            <button onclick="generarLibroXLS('DIARIO')" class="px-4 bg-emerald-600 text-white rounded-2xl hover:bg-emerald-700 transition shadow-lg shadow-black/10" title="Exportar a Excel">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </button>
                        </div>
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
                        <div class="flex gap-2 mt-6">
                            <button onclick="generarLibro('MAYOR')" class="flex-1 py-4 bg-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-emerald-600 transition shadow-lg shadow-black/10">Generar Libro Mayor</button>
                            <button onclick="generarLibroXLS('MAYOR')" class="px-4 bg-emerald-600 text-white rounded-2xl hover:bg-emerald-700 transition shadow-lg shadow-black/10" title="Exportar a Excel">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Inventarios y Balances -->
                <div class="bg-white rounded-[2.5rem] border border-slate-200 p-10 shadow-sm hover:shadow-2xl transition group relative overflow-hidden">
                    <div class="absolute -right-8 -top-8 w-32 h-32 bg-rose-50 rounded-full blur-2xl group-hover:bg-rose-100 transition duration-500"></div>
                    <h3 class="text-2xl font-black text-slate-800 mb-2 relative">Balance General</h3>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-6 border-b border-slate-50 pb-4">Balance General e Inventarios</p>
                    
                    <div class="space-y-4">
                         <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Periodo de Balances (Reales)</label>
                            <select id="p_inv" class="w-full h-11 border border-slate-200 rounded-2xl px-4 outline-none text-xs font-black shadow-sm">
                                <?php 
                                    // Mostramos todos los años que existen en la tabla de periodos o comprobantes
                                    $sqlYears = "SELECT anio FROM (
                                                    SELECT DISTINCT anio FROM periodos WHERE empresa_id = :eid
                                                    UNION
                                                    SELECT DISTINCT YEAR(fecha) as anio FROM comprobantes WHERE empresa_id = :eid2
                                                 ) as t 
                                                 ORDER BY anio DESC";
                                    $stmtYears = $db->prepare($sqlYears);
                                    $stmtYears->execute([':eid' => $eid, ':eid2' => $eid]);
                                    $periodosYears = $stmtYears->fetchAll();
                                    if (!empty($balanceYears)) {
                                        $periodosYears = array_map(
                                            static fn (int $anio): array => ['anio' => $anio],
                                            $balanceYears
                                        );
                                    }

                                    if (empty($periodosYears)) {
                                        echo "<option value='".date('Y')."'>Año Actual: ".date('Y')."</option>";
                                    } else {
                                        foreach($periodosYears as $py): 
                                ?>
                                <option value="<?= $py['anio'] ?>" <?= (int)$py['anio'] === $latestYearWithData ? 'selected' : '' ?>>Balance Anual: <?= $py['anio'] ?></option>
                                <?php 
                                        endforeach; 
                                    }
                                ?>
                            </select>
                        </div>
                        <?php 
                            $assignedPids = \ContaFC\Core\Auth::getAssignedProjectIds();
                            $isAdmin = \ContaFC\Core\Auth::user()['rol'] === 'admin';
                            
                            if ($isAdmin) {
                                $stmtProy = $db->prepare("SELECT id, nombre, codigo FROM proyectos WHERE empresa_id = :eid AND activo = 1 ORDER BY nombre ASC");
                                $stmtProy->execute([':eid' => $eid]);
                                $proyectosList = $stmtProy->fetchAll();
                            } else {
                                if (empty($assignedPids)) {
                                    $proyectosList = [];
                                } else {
                                    $placeholders = implode(',', array_fill(0, count($assignedPids), '?'));
                                    $stmtProy = $db->prepare("SELECT id, nombre, codigo FROM proyectos WHERE empresa_id = ? AND id IN ($placeholders) AND activo = 1 ORDER BY nombre ASC");
                                    $stmtProy->execute(array_merge([$eid], $assignedPids));
                                    $proyectosList = $stmtProy->fetchAll();
                                }
                            }
                            
                            $showDropdown = $isAdmin || count($proyectosList) > 1;
                            $singleProjectId = (!$isAdmin && count($proyectosList) === 1) ? $proyectosList[0]['id'] : '';
                        ?>
                        <div class="space-y-1" <?= !$showDropdown ? 'style="display:none;"' : '' ?>>
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Proyecto / Centro de Beneficio</label>
                            <select id="p_inv_proy" class="w-full h-11 border border-slate-200 rounded-2xl px-4 outline-none text-xs font-black shadow-sm">
                                <?php if ($isAdmin): ?>
                                <option value="" selected>-- Todos los Proyectos --</option>
                                <?php endif; ?>
                                <?php foreach($proyectosList as $proy): ?>
                                <option value="<?= $proy['id'] ?>" <?= $proy['id'] == $singleProjectId ? 'selected' : '' ?>>
                                    <?= $proy['codigo'] ?> - <?= $proy['nombre'] ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Formato de Presentación</label>
                            <select id="sub_inv" class="w-full h-11 border border-slate-200 rounded-2xl px-4 outline-none text-xs font-black shadow-sm">
                                <option value="auxiliar">Balance general vertical</option>
                                <option value="capital">Balance general horizontal</option>
                            </select>
                        </div>
                        <div class="flex gap-2 mt-6">
                            <button onclick="generarLibro('INVENTARIOS')" class="flex-1 py-4 bg-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-rose-600 transition shadow-lg shadow-black/10">Generar Balance</button>
                            <button onclick="generarLibroXLS('INVENTARIOS')" class="px-4 bg-emerald-600 text-white rounded-2xl hover:bg-emerald-700 transition shadow-lg shadow-black/10" title="Exportar a Excel">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Estado de Resultados -->
                <div class="bg-white rounded-[2.5rem] border border-slate-200 p-10 shadow-sm hover:shadow-2xl transition group relative overflow-hidden">
                    <div class="absolute -right-8 -top-8 w-32 h-32 bg-amber-50 rounded-full blur-2xl group-hover:bg-amber-100 transition duration-500"></div>
                    <h3 class="text-2xl font-black text-slate-800 mb-2 relative">Estado de Resultados</h3>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-6 border-b border-slate-50 pb-4">Ingresos, Gastos y Utilidad</p>
                    
                    <div class="space-y-4">
                         <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Año Fiscal</label>
                            <select id="p_res_year" class="w-full h-11 border border-slate-200 rounded-2xl px-4 outline-none text-xs font-black shadow-sm">
                                <?php 
                                    foreach($periodosYears as $py): 
                                ?>
                                <option value="<?= $py['anio'] ?>" <?= (int)$py['anio'] === $latestYearWithData ? 'selected' : '' ?>>Año: <?= $py['anio'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="space-y-1" <?= !$showDropdown ? 'style="display:none;"' : '' ?>>
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Proyecto / Centro de Beneficio</label>
                            <select id="p_res_proy" class="w-full h-11 border border-slate-200 rounded-2xl px-4 outline-none text-xs font-black shadow-sm">
                                <?php if ($isAdmin): ?>
                                <option value="" selected>-- Todos los Proyectos --</option>
                                <?php endif; ?>
                                <?php foreach($proyectosList as $proy): ?>
                                <option value="<?= $proy['id'] ?>" <?= $proy['id'] == $singleProjectId ? 'selected' : '' ?>>
                                    <?= $proy['codigo'] ?> - <?= $proy['nombre'] ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex gap-2 mt-6">
                            <button onclick="generarLibro('RESULTADOS')" class="flex-1 py-4 bg-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-amber-600 transition shadow-lg shadow-black/10">Generar Resultados</button>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</main>

<script>
function getVal(id) {
    const el = document.getElementById(id);
    if (!el) {
        console.error('Elemento no encontrado:', id);
        return null;
    }
    return el.value;
}

function generarLibro(tipo, format = 'pdf') {
    const idMap = { 'DIARIO': 'p_diario', 'MAYOR': 'p_mayor', 'INVENTARIOS': 'p_inv', 'RESULTADOS': 'p_res_year' };
    
    const pId = getVal(idMap[tipo]);
    const folio = '1';
    
    if (pId === null) {
        Swal.fire('Error', `No se pudo encontrar el selector para ${tipo}. Por favor recarga la página (CTRL+F5).`, 'error');
        return;
    }

    let extra = '';
    if (tipo === 'INVENTARIOS') {
        const sub = getVal('sub_inv');
        const proyId = getVal('p_inv_proy');
        if (sub) extra = `&subtipo=${sub}`;
        if (proyId) extra += `&proyecto_id=${proyId}`;
    } else if (tipo === 'RESULTADOS') {
        const proyId = getVal('p_res_proy');
        if (proyId) extra += `&proyecto_id=${proyId}`;
    }

    const ts = Date.now();
    const url = `<?= BASE_URL ?>/api/libros_oficiales.php?tipo=${tipo}&pid=${pId}&folio=${folio}${extra}${format === 'excel' ? '&format=excel' : ''}&_t=${ts}`;

    if (format === 'excel') {
        window.open(url);
    } else {
        Swal.fire({
            title: `Generando ${tipo === 'INVENTARIOS' ? 'Balance General' : 'Libro ' + tipo}`,
            html: `<div class='p-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest'>Procesando datos y preparando visualización...</div>`,
            timer: 1000,
            didOpen: () => Swal.showLoading(),
            willClose: () => { window.open(url); }
        });
    }
}

// Alias para mantener compatibilidad con los onclick existentes
const generarLibroXLS = (tipo) => generarLibro(tipo, 'excel');

document.addEventListener('DOMContentLoaded', () => {
    const proyectoSelect = document.getElementById('p_inv_proy');
    if (proyectoSelect) {
        proyectoSelect.value = '';
    }
});
</script>

</body>
</html>
