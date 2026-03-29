<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requirePermiso('asiento');
$user    = Auth::user();
$empresa = null;
$tiposComp  = [];
$periodoAct = null;

try {
    $db = Database::getInstance()->getPdo();
    $empresa   = $db->query("SELECT * FROM empresas WHERE id = " . Auth::empresaId())->fetch();
    $tiposComp = $db->query(
        "SELECT id, codigo, nombre FROM tipos_comprobante
         WHERE empresa_id = " . Auth::empresaId() . " AND activo = 1 ORDER BY codigo"
    )->fetchAll();
    $periodoAct = $db->prepare(
        "SELECT * FROM periodos WHERE empresa_id = :eid AND estado = 'abierto' AND anio = YEAR(NOW()) AND mes = MONTH(NOW())"
    );
    $periodoAct->execute([':eid' => Auth::empresaId()]);
    $periodoAct = $periodoAct->fetch();
} catch (\Throwable) {}

// ¿Estamos viendo un comprobante existente?
$editId = isset($_GET['id']) ? (int)$_GET['id'] : null;
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ContaFC – Registro de Asiento | <?= htmlspecialchars($empresa['nombre'] ?? '') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT:'#1e3a5f', light:'#2563eb', dark:'#0f1f3d' },
                        accent: '#f59e0b',
                    },
                    fontFamily: { sans: ['Inter','system-ui','sans-serif'] }
                }
            }
        }
    </script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .sidebar-link { display:flex; align-items:center; gap:.75rem; padding:.625rem 1rem;
                        border-radius:.5rem; color:#94a3b8; font-size:.875rem; font-weight:500;
                        transition:all .15s; }
        .sidebar-link:hover { background:rgba(255,255,255,.08); color:#fff; }
        .sidebar-link.active { background:#2563eb; color:#fff; box-shadow:0 4px 14px rgba(37,99,235,.35); }
        .grid-header { background:#1e3a5f; color:#fff; font-size:.7rem; font-weight:600;
                       text-transform:uppercase; letter-spacing:.05em; }
        .grid-row { transition:background .1s; }
        .grid-row:hover { background:#eff6ff; }
        .grid-row.selected { background:#dbeafe; outline:2px solid #2563eb; outline-offset:-1px; }
        .grid-input { border:none; background:transparent; width:100%; font-size:.8125rem;
                      padding:.375rem .5rem; outline:none; font-family:inherit; }
        .grid-input:focus { background:#eff6ff; }
        td.editing { padding:0!important; }
        .totals-bar { background:#1e3a5f; color:#fff; font-size:.875rem; border-radius:0 0 .75rem .75rem; }
        .badge-cuadra { background:#10b981; }
        .badge-descuadre { background:#ef4444; }
        .autocomplete-dropdown { position:absolute; z-index:50; background:white; border:1px solid #e2e8f0;
                                 border-radius:.5rem; box-shadow:0 10px 25px rgba(0,0,0,.15); max-height:200px;
                                 overflow-y:auto; width:100%; min-width:280px; }
        .autocomplete-item { padding:.5rem .75rem; font-size:.8125rem; cursor:pointer; border-bottom:1px solid #f1f5f9; }
        .autocomplete-item:hover, .autocomplete-item.sel { background:#eff6ff; color:#1e40af; }
        @keyframes fadeIn { from{opacity:0;transform:translateY(-4px)} to{opacity:1;transform:translateY(0)} }
        .fade-in { animation:fadeIn .2s ease-out; }
    </style>
</head>
<body class="h-full font-sans flex">
<?php $activeNav = 'asiento'; require __DIR__ . '/partials/sidebar.php'; ?>

<!-- ─── Contenido principal ──────────────────────────────────────────────── -->
<main class="flex-1 overflow-auto">
    <!-- Toolbar estilo WX-Manager -->
    <header class="bg-white border-b border-slate-200 px-5 py-2.5 flex items-center gap-2 sticky top-0 z-20 flex-wrap">
        <!-- Botones de acción estilo toolbar -->
        <button id="btn-nuevo" title="Nuevo comprobante" onclick="nuevoComprobante()"
                class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-lg hover:bg-slate-100 text-slate-600 hover:text-slate-900 transition-all text-xs font-medium">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nuevo
        </button>
        <button id="btn-guardar" title="Guardar comprobante" onclick="guardarComprobante()"
                class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-lg hover:bg-blue-50 text-blue-600 hover:text-blue-800 transition-all text-xs font-medium">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
            Guardar
        </button>
        <button id="btn-anular" title="Anular comprobante" onclick="anularComprobante()"
                class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-lg hover:bg-red-50 text-red-500 hover:text-red-700 transition-all text-xs font-medium">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            Anular
        </button>
        <div class="w-px h-10 bg-slate-200 mx-1"></div>
        <button onclick="addLinea()" title="Agregar línea"
                class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-lg hover:bg-green-50 text-green-600 hover:text-green-800 transition-all text-xs font-medium">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Añadir
        </button>
        <button onclick="eliminarLineaSelected()" title="Eliminar línea seleccionada"
                class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-lg hover:bg-red-50 text-slate-400 hover:text-red-600 transition-all text-xs font-medium">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Eliminar
        </button>

        <div class="flex-1"></div>

        <!-- Indicador de empresa/usuario/periodo -->
        <div class="flex gap-4 text-xs text-slate-500 flex-wrap">
            <span><b class="text-slate-700">E:</b> <?= Auth::empresaId() ?></span>
            <span><b class="text-slate-700">S:</b> 1</span>
            <span><b class="text-slate-700">Usuario:</b> <?= htmlspecialchars($user['username'] ?? '') ?></span>
            <span class="<?= $periodoAct ? 'text-green-600' : 'text-red-500' ?>">
                <b>Período:</b> <?= $periodoAct ? date('M Y', mktime(0,0,0,(int)$periodoAct['mes'],1,(int)$periodoAct['anio'])) : 'CERRADO' ?>
            </span>
        </div>
    </header>

    <!-- Cabecera del comprobante -->
    <div class="bg-white border-b border-slate-200 px-5 py-3">
        <div class="flex flex-wrap gap-3 items-end">
            <!-- Tipo documento -->
            <div class="flex-shrink-0">
                <label class="block text-xs text-slate-500 mb-1 font-medium">Documento</label>
                <select id="tipo_comp_id" name="tipo_comp_id"
                        class="h-8 px-2 border border-slate-300 rounded text-sm bg-blue-50 font-medium text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <?php foreach ($tiposComp as $tc): ?>
                    <option value="<?= $tc['id'] ?>" data-codigo="<?= $tc['codigo'] ?>">
                        <?= htmlspecialchars($tc['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Número -->
            <div class="flex-shrink-0 w-20">
                <label class="block text-xs text-slate-500 mb-1 font-medium">Nº</label>
                <input id="numero_comp" type="text" readonly value="AUTO"
                       class="h-8 px-2 w-full border border-slate-200 rounded text-sm bg-slate-50 text-slate-500 font-mono" title="Consecutivo automático">
            </div>

            <!-- Tercero (autocomplete) -->
            <div class="flex-1 min-w-48 relative" id="wrap-tercero">
                <label class="block text-xs text-slate-500 mb-1 font-medium">Tercero</label>
                <input id="tercero_input" type="text" autocomplete="off" placeholder="Buscar por RTN o nombre..."
                       class="h-8 px-2 w-full border border-slate-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <input type="hidden" id="tercero_id">
                <div id="drop-tercero" class="autocomplete-dropdown hidden fade-in"></div>
            </div>

            <!-- Fecha -->
            <div class="flex-shrink-0">
                <label class="block text-xs text-slate-500 mb-1 font-medium">Fecha</label>
                <input id="fecha" type="date" value="<?= date('Y-m-d') ?>"
                       class="h-8 px-2 border border-slate-300 rounded text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Totales en tiempo real -->
            <div class="flex-shrink-0 flex gap-3 ml-2">
                <div class="text-xs">
                    <div class="text-slate-500 mb-0.5">Total Débitos</div>
                    <div id="tot-deb" class="font-mono font-bold text-emerald-700">$ 0.00</div>
                </div>
                <div class="text-xs">
                    <div class="text-slate-500 mb-0.5">Total Créditos</div>
                    <div id="tot-cre" class="font-mono font-bold text-blue-700">$ 0.00</div>
                </div>
                <div class="text-xs">
                    <div class="text-slate-500 mb-0.5">Diferencia</div>
                    <div id="tot-dif" class="font-mono font-bold text-red-600">$ 0.00</div>
                </div>
                <div class="flex items-end pb-0.5">
                    <span id="badge-pd" class="px-2 py-0.5 rounded-full text-xs font-bold text-white badge-descuadre opacity-60">PD ✗</span>
                </div>
            </div>
        </div>

        <!-- Observaciones -->
        <div class="mt-2">
            <input id="observaciones" type="text" placeholder="Observaciones del comprobante..."
                   class="w-full h-7 px-2 border border-slate-200 rounded text-sm text-slate-700 bg-slate-50 focus:ring-2 focus:ring-blue-500 focus:bg-white">
        </div>
    </div>

    <!-- ─── Grid de Asientos (tabla editable estilo WX-Manager) ─────────── -->
    <div id="comp-id-data" data-id="<?= $editId ?? '' ?>"></div>
    <div class="px-4 py-3 max-w-full">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-xs border-collapse" id="grid-asientos">
                    <thead>
                        <tr class="grid-header">
                            <th class="px-2 py-2 text-left w-8">#</th>
                            <th class="px-2 py-2 text-left w-32">Código Cta.</th>
                            <th class="px-2 py-2 text-left min-w-48">Nombre Cuenta</th>
                            <th class="px-2 py-2 text-left w-8 text-center" title="Destino">D</th>
                            <th class="px-2 py-2 text-left w-40">Tercero</th>
                            <th class="px-2 py-2 text-left w-20">Doc.Tipo</th>
                            <th class="px-2 py-2 text-left w-24">Doc.Número</th>
                            <th class="px-2 py-2 text-left w-24">Vencimiento</th>
                            <th class="px-2 py-2 text-left w-24">Proyecto</th>
                            <th class="px-2 py-2 text-left w-20">Centro C.</th>
                            <th class="px-2 py-2 text-right w-32">Débito</th>
                            <th class="px-2 py-2 text-right w-32">Crédito</th>
                            <th class="px-2 py-2 text-center w-20">ISV (%)</th>
                            <th class="px-2 py-2 text-left min-w-40">Descripción</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpo-grid">
                        <!-- Las filas se generan dinámicamente -->
                    </tbody>
                </table>
            </div>
            <!-- Barra de totales -->
            <div class="totals-bar px-4 py-2 flex justify-end gap-8 text-sm">
                <span>Cuenta: <span id="foot-cuenta" class="font-mono ml-2">—</span></span>
                <span>Base: <span id="foot-base" class="font-mono ml-2">—</span></span>
                <span>Beneficiario: <span id="foot-benef" class="font-mono ml-2">—</span></span>
            </div>
        </div>
    </div>

    <!-- Estado lote -->
    <div class="px-5 py-2 text-xs text-slate-500 flex gap-4">
        <span>Versión 1.0 · MySQL 8.0</span>
        <span id="status-msg" class="text-blue-600"></span>
    </div>
</main>

<!-- ─── JavaScript principal ─────────────────────────────────────────────── -->
<script>
// ═══════════════════════════════════════════════════════════════════════════
// ContaFC · Estado del formulario
// ═══════════════════════════════════════════════════════════════════════════
let lineas       = [];
let lineaSelIdx  = -1;
let comprobanteId = <?= $editId ?? 'null' ?>;

const DEBOUNCE_MS = 280;

// ─── Inicialización ───────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // Añadir 3 líneas vacías por defecto
    addLinea(); addLinea(); addLinea();
    renderGrid();
    setupAutocomplete('tercero_input', 'drop-tercero', '<?= BASE_URL ?>/api/terceros.php', 'tercero_id', (it) => it.nit_cc + ' – ' + it.nombre);
    if (comprobanteId) cargarComprobante(comprobanteId);
});

// ─── Modelo de línea ─────────────────────────────────────────────────────
function nuevaLinea() {
    return { cuenta_id: null, cuenta_codigo: '', cuenta_nombre: '', tercero_id: null, tercero_nombre: '',
             doc_cruce_tipo: '', doc_cruce_num: '', vencimiento: '', proyecto_nombre: '', ceco_nombre: '',
             debito: 0, credito: 0, descripcion: '' };
}

function addLinea() {
    lineas.push(nuevaLinea());
    renderGrid();
    actualizarTotales();
    // Focus en la última fila, celda cuenta
    setTimeout(() => {
        const tbody = document.getElementById('cuerpo-grid');
        const lastRow = tbody.lastElementChild;
        if (lastRow) lastRow.querySelector('.cell-cuenta-cod')?.focus();
    }, 50);
}

// ─── Renderizar grid ─────────────────────────────────────────────────────
function renderGrid() {
    const tbody = document.getElementById('cuerpo-grid');
    tbody.innerHTML = lineas.map((l, i) => `
    <tr class="grid-row border-b border-slate-100 ${lineaSelIdx === i ? 'selected' : ''}"
        id="row-${i}" onclick="selectLinea(${i})">
        <td class="px-2 py-1 text-slate-400 w-8 font-mono">${i+1}</td>
        <td class="editing w-32 relative">
            <input type="text" class="grid-input cell-cuenta-cod font-mono" value="${esc(l.cuenta_codigo)}"
                   placeholder="Código..."
                   onchange="setCuentaByCodigo(${i}, this.value)"
                   onkeydown="handleGridKey(event, ${i}, 0)"
                   id="inp-cuenta-cod-${i}">
        </td>
        <td class="px-2 py-1 text-slate-700 min-w-48 truncate max-w-xs italic text-slate-500" id="cell-nombre-${i}">
            ${esc(l.cuenta_nombre) || '<span class="text-slate-300">Buscar cuenta...</span>'}
        </td>
        <td class="px-2 py-1 text-center text-slate-400 w-8">→</td>
        <td class="editing w-40">
            <input type="text" class="grid-input cell-tercero" value="${esc(l.tercero_nombre)}"
                   placeholder="Tercero..."
                   oninput="searchTerceroLinea(${i}, this.value)"
                   id="inp-tercero-${i}">
        </td>
        <td class="editing w-20">
            <input type="text" class="grid-input font-mono" value="${esc(l.doc_cruce_tipo)}"
                   placeholder="Tipo" oninput="lineas[${i}].doc_cruce_tipo=this.value">
        </td>
        <td class="editing w-24">
            <input type="text" class="grid-input font-mono" value="${esc(l.doc_cruce_num)}"
                   placeholder="Número" oninput="lineas[${i}].doc_cruce_num=this.value">
        </td>
        <td class="editing w-24">
            <input type="date" class="grid-input text-xs" value="${esc(l.vencimiento)}"
                   onchange="lineas[${i}].vencimiento=this.value">
        </td>
        <td class="px-2 py-1 text-slate-400 text-xs">—</td>
        <td class="px-2 py-1 text-slate-400 text-xs">—</td>
        <td class="editing w-32">
            <input type="number" class="grid-input text-right font-mono text-emerald-700"
                   value="${l.debito > 0 ? l.debito.toFixed(2) : ''}"
                   placeholder="0.00" step="0.01" min="0"
                   onchange="setDebito(${i}, this.value)"
                   id="inp-deb-${i}">
        </td>
        <td class="editing w-32">
            <input type="number" class="grid-input text-right font-mono text-blue-700"
                   value="${l.credito > 0 ? l.credito.toFixed(2) : ''}"
                   placeholder="0.00" step="0.01" min="0"
                   onchange="setCredito(${i}, this.value)"
                   id="inp-cre-${i}">
        </td>
        <td class="editing w-20">
            <select class="grid-input text-center font-bold text-honduras" onchange="calcularISV(${i}, this.value)">
                <option value="0">0%</option>
                <option value="15" ${l.isv_tasa == 15 ? 'selected' : ''}>15%</option>
                <option value="18" ${l.isv_tasa == 18 ? 'selected' : ''}>18%</option>
            </select>
        </td>
        <td class="editing min-w-40">
            <input type="text" class="grid-input text-slate-600" value="${esc(l.descripcion)}"
                   placeholder="Descripción del movimiento..."
                   oninput="lineas[${i}].descripcion=this.value">
        </td>
    </tr>
    `).join('');
}

// ─── Selección de fila ────────────────────────────────────────────────────
function selectLinea(idx) {
    lineaSelIdx = idx;
    document.querySelectorAll('#cuerpo-grid tr').forEach((r, i) => r.classList.toggle('selected', i === idx));
    const l = lineas[idx];
    document.getElementById('foot-cuenta').textContent = l.cuenta_codigo || '—';
    document.getElementById('foot-benef').textContent  = l.tercero_nombre || '—';
}

// ─── Setters de campo ─────────────────────────────────────────────────────
function setDebito(idx, val) {
    lineas[idx].debito  = parseFloat(val) || 0;
    lineas[idx].credito = 0;
    actualizarTotales();
}
function setCredito(idx, val) {
    lineas[idx].credito = parseFloat(val) || 0;
    lineas[idx].debito  = 0;
    actualizarTotales();
}

// ─── Actualizar totales en tiempo real ───────────────────────────────────
function actualizarTotales() {
    const totDeb = lineas.reduce((s, l) => s + l.debito,  0);
    const totCre = lineas.reduce((s, l) => s + l.credito, 0);
    const dif    = Math.abs(totDeb - totCre);
    const cuadra = dif < 0.01;

    document.getElementById('tot-deb').textContent = fmt(totDeb);
    document.getElementById('tot-cre').textContent = fmt(totCre);
    document.getElementById('tot-dif').textContent = fmt(dif);

    const badge = document.getElementById('badge-pd');
    badge.textContent = cuadra ? 'PD ✓' : 'PD ✗';
    badge.className   = 'px-2 py-0.5 rounded-full text-xs font-bold text-white ' + (cuadra ? 'badge-cuadra' : 'badge-descuadre');
    badge.style.opacity = '1';
}

// ─── Asistente de ISV (Honduras) ──────────────────────────────────────────
async function calcularISV(idx, tasa) {
    tasa = parseFloat(tasa);
    if (tasa === 0) return;

    const l = lineas[idx];
    const base = l.debito || l.credito;
    if (base <= 0) return;

    const impuesto = parseFloat((base * (tasa / 100)).toFixed(2));
    const isDebito = l.debito > 0;

    const confirm = await Swal.fire({
        title: `Calcular ISV ${tasa}%`,
        text: `¿Deseas agregar una línea de ISV por L. ${impuesto.toFixed(2)}?`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Sí, agregar',
        cancelButtonText: 'No'
    });

    if (confirm.isConfirmed) {
        const nuevaL = nuevaLinea();
        nuevaL.debito  = isDebito ? impuesto : 0;
        nuevaL.credito = !isDebito ? impuesto : 0;
        nuevaL.descripcion = `ISV ${tasa}% de: ${l.cuenta_nombre || l.cuenta_codigo}`;
        
        // Intentar pre-cargar cuenta de ISV por defecto (Honduras)
        // 1106-01 (ISV Pagado) o 2103-01 (ISV Cobrado) ?
        const ctaISV = isDebito ? '110601' : '210301'; 
        lineas.splice(idx + 1, 0, nuevaL);
        renderGrid();
        actualizarTotales();
        setCuentaByCodigo(idx + 1, ctaISV);
    }
}

// ─── Buscar cuenta por código ─────────────────────────────────────────────
async function setCuentaByCodigo(idx, codigo) {
    if (!codigo.trim()) return;
    try {
        const r = await fetch(`<?= BASE_URL ?>/api/cuentas.php?q=${encodeURIComponent(codigo.trim())}`);
        const j = await r.json();
        if (j.data?.length) {
            const cuenta = j.data[0];
            lineas[idx].cuenta_id     = cuenta.id;
            lineas[idx].cuenta_codigo = cuenta.codigo;
            lineas[idx].cuenta_nombre = cuenta.nombre;
            document.getElementById(`cell-nombre-${idx}`).textContent = cuenta.nombre;
            document.getElementById(`inp-cuenta-cod-${idx}`).value    = cuenta.codigo;
            // Mover foco al campo débito/crédito según naturaleza
            if (cuenta.naturaleza === 'D') {
                document.getElementById(`inp-deb-${idx}`)?.focus();
            } else {
                document.getElementById(`inp-cre-${idx}`)?.focus();
            }
        }
    } catch(e) {}
}

let terceroTimer = {};
async function searchTerceroLinea(idx, q) {
    lineas[idx].tercero_nombre = q;
    clearTimeout(terceroTimer[idx]);
    if (q.length < 2) return;
    terceroTimer[idx] = setTimeout(async () => {
        const r = await fetch(`<?= BASE_URL ?>/api/terceros.php?q=${encodeURIComponent(q)}`);
        const j = await r.json();
        // Aquí se podría implementar un mini autocomplete por fila
        // Por simplicidad, al perder el foco guardamos el texto y luego validamos con el servidor
    }, DEBOUNCE_MS);
}

// ─── Autocomplete genérico (cabecera) ─────────────────────────────────────
let acTimers = {};
function setupAutocomplete(inputId, dropId, apiUrl, hiddenId, labelFn) {
    const inp  = document.getElementById(inputId);
    const drop = document.getElementById(dropId);
    let selIdx = -1;

    inp.addEventListener('input', () => {
        clearTimeout(acTimers[inputId]);
        const q = inp.value.trim();
        if (q.length < 2) { drop.classList.add('hidden'); return; }
        acTimers[inputId] = setTimeout(async () => {
            const r = await fetch(`${apiUrl}?q=${encodeURIComponent(q)}`);
            const j = await r.json();
            const items = j.data || [];
            if (!items.length) { drop.classList.add('hidden'); return; }
            selIdx = -1;
            drop.innerHTML = items.map((it, i) =>
                `<div class="autocomplete-item" data-id="${it.id}" data-label="${esc(labelFn(it))}"
                      onmousedown="selectAcItem('${inputId}','${dropId}','${hiddenId}',this)">${labelFn(it)}</div>`
            ).join('');
            drop.classList.remove('hidden');
        }, DEBOUNCE_MS);
    });

    inp.addEventListener('keydown', (e) => {
        const items = drop.querySelectorAll('.autocomplete-item');
        if (e.key === 'ArrowDown') { selIdx = Math.min(selIdx+1, items.length-1); highlightAc(items, selIdx); e.preventDefault(); }
        if (e.key === 'ArrowUp')   { selIdx = Math.max(selIdx-1, 0); highlightAc(items, selIdx); e.preventDefault(); }
        if (e.key === 'Enter' && selIdx >= 0) { items[selIdx].dispatchEvent(new Event('mousedown')); e.preventDefault(); }
        if (e.key === 'Escape') drop.classList.add('hidden');
    });

    document.addEventListener('click', (e) => { if (!e.target.closest(`#wrap-${inputId.replace('_input','')}`)) drop.classList.add('hidden'); });
}

function highlightAc(items, idx) {
    items.forEach((el, i) => el.classList.toggle('sel', i === idx));
}

function selectAcItem(inputId, dropId, hiddenId, el) {
    document.getElementById(inputId).value = el.dataset.label;
    document.getElementById(hiddenId).value = el.dataset.id;
    document.getElementById(dropId).classList.add('hidden');
}

// ─── Eliminar línea seleccionada ─────────────────────────────────────────
function eliminarLineaSelected() {
    if (lineaSelIdx < 0 || lineas.length <= 2) {
        Swal.fire({ icon:'warning', title:'No se puede eliminar', text:'Debe quedar al menos 2 líneas en el asiento.', timer:2500, showConfirmButton:false, toast:true, position:'top-end' });
        return;
    }
    lineas.splice(lineaSelIdx, 1);
    lineaSelIdx = -1;
    renderGrid();
    actualizarTotales();
}

// ─── Nuevo comprobante ────────────────────────────────────────────────────
function nuevoComprobante() {
    lineas = [];
    lineaSelIdx = -1;
    comprobanteId = null;
    document.getElementById('tercero_input').value = '';
    document.getElementById('tercero_id').value    = '';
    document.getElementById('observaciones').value  = '';
    document.getElementById('fecha').value          = new Date().toISOString().substring(0, 10);
    addLinea(); addLinea(); addLinea();
    actualizarTotales();
    document.getElementById('status-msg').textContent = 'Nuevo comprobante listo.';
}

// ─── Guardar comprobante (con SweetAlert2) ─────────────────────────────
async function guardarComprobante() {
    const totDeb = lineas.reduce((s, l) => s + l.debito,  0);
    const totCre = lineas.reduce((s, l) => s + l.credito, 0);
    const dif    = Math.abs(totDeb - totCre);

    if (dif > 0.01) {
        await Swal.fire({
            icon: 'error',
            title: '¡Partida Doble Descuadrada!',
            html: `
                <div class="text-left space-y-2 mt-2">
                    <div class="flex justify-between bg-red-50 p-2 rounded"><span class="text-slate-600">Total Débitos:</span><span class="font-mono font-bold text-emerald-700">${fmt(totDeb)}</span></div>
                    <div class="flex justify-between bg-red-50 p-2 rounded"><span class="text-slate-600">Total Créditos:</span><span class="font-mono font-bold text-blue-700">${fmt(totCre)}</span></div>
                    <div class="flex justify-between bg-red-100 p-2 rounded border border-red-200"><span class="text-red-700 font-semibold">Diferencia:</span><span class="font-mono font-bold text-red-700">${fmt(dif)}</span></div>
                </div>`,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Corregir',
        });
        return;
    }

    const result = await Swal.fire({
        title: '¿Registrar comprobante?',
        html: `
            <div class="text-sm text-slate-600 space-y-1 mt-2">
                <div><b>Tipo:</b> ${document.getElementById('tipo_comp_id').options[document.getElementById('tipo_comp_id').selectedIndex].text}</div>
                <div><b>Fecha:</b> ${document.getElementById('fecha').value}</div>
                <div><b>Débitos / Créditos:</b> ${fmt(totDeb)}</div>
                <div><b>Líneas:</b> ${lineas.filter(l=>l.cuenta_id||l.debito||l.credito).length}</div>
            </div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '✓ Registrar',
        cancelButtonText:  'Cancelar',
        reverseButtons: true,
    });

    if (!result.isConfirmed) return;

    // Preparar payload
    const lineasValidas = lineas.filter(l => l.cuenta_id && (l.debito > 0 || l.credito > 0));
    const payload = {
        tipo_comp_id:  parseInt(document.getElementById('tipo_comp_id').value),
        fecha:         document.getElementById('fecha').value,
        tercero_id:    document.getElementById('tercero_id').value || null,
        observaciones: document.getElementById('observaciones').value || null,
        lineas:        lineasValidas.map(l => ({
            cuenta_id:    l.cuenta_id,
            debito:       l.debito,
            credito:      l.credito,
            descripcion:  l.descripcion || null,
            tercero_id:   l.tercero_id || null,
        })),
    };

    try {
        Swal.fire({ title:'Guardando...', didOpen: () => Swal.showLoading(), allowOutsideClick: false });

        const res  = await fetch('<?= BASE_URL ?>/api/comprobantes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const json = await res.json();

        if (res.ok && json.success) {
            comprobanteId = json.comprobante_id;
            await Swal.fire({
                icon: 'success',
                title: '¡Comprobante Registrado!',
                html: `<div class="text-slate-600">ID: <span class="font-mono font-bold text-blue-700">#${json.comprobante_id}</span></div>`,
                timer: 3500,
                timerProgressBar: true,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
            });
            document.getElementById('status-msg').textContent = `Comprobante #${json.comprobante_id} guardado.`;
        } else {
            await Swal.fire({ icon:'error', title:'Error al guardar', text: json.error || 'Error desconocido' });
        }
    } catch(e) {
        await Swal.fire({ icon:'error', title:'Error de conexión', text: 'No se pudo conectar con el servidor.' });
    }
}

// ─── Anular comprobante (SweetAlert2 confirm) ─────────────────────────────
async function anularComprobante() {
    if (!comprobanteId) {
        Swal.fire({ icon:'warning', title:'Sin comprobante', text:'Cargue un comprobante registrado para anularlo.', toast:true, position:'top-end', showConfirmButton:false, timer:3000 });
        return;
    }

    const { value: motivo } = await Swal.fire({
        title: '⚠️ Anular Comprobante',
        html: `
            <div class="text-sm text-slate-600 mb-3">Esta acción no se puede deshacer. El comprobante
            <b class="text-red-600">#${comprobanteId}</b> quedará anulado y se revertirán sus saldos.</div>
            <textarea id="swal-motivo" class="swal2-textarea" rows="3" placeholder="Motivo de anulación (opcional)..."></textarea>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '🗑️ Sí, Anular',
        cancelButtonText:  'Cancelar',
        reverseButtons: true,
        preConfirm: () => document.getElementById('swal-motivo').value,
    });

    if (typeof value === 'undefined' && !result?.isConfirmed) return;

    try {
        Swal.fire({ title:'Anulando...', didOpen: () => Swal.showLoading(), allowOutsideClick: false });
        const res  = await fetch(`<?= BASE_URL ?>/api/comprobantes.php?id=${comprobanteId}`, { method: 'DELETE' });
        const json = await res.json();

        if (res.ok && json.success) {
            await Swal.fire({ icon:'success', title:'Anulado correctamente', text: json.message, timer:3000, timerProgressBar:true });
            nuevoComprobante();
        } else {
            await Swal.fire({ icon:'error', title:'Error al anular', text: json.error });
        }
    } catch(e) {
        await Swal.fire({ icon:'error', title:'Error de conexión' });
    }
}

// ─── Cargar comprobante existente ─────────────────────────────────────────
async function cargarComprobante(id) {
    const res  = await fetch(`<?= BASE_URL ?>/api/comprobantes.php?id=${id}`);
    const data = await res.json();
    if (!res.ok) { Swal.fire({ icon:'error', title:'Error', text: data.error }); return; }

    document.getElementById('fecha').value          = data.fecha;
    document.getElementById('observaciones').value  = data.observaciones || '';
    document.getElementById('tercero_input').value  = data.tercero || '';
    // Seleccionar tipo
    const sel = document.getElementById('tipo_comp_id');
    for (let i=0; i<sel.options.length; i++) {
        if (sel.options[i].dataset.codigo === data.tipo_codigo) { sel.selectedIndex = i; break; }
    }

    lineas = (data.lineas || []).map(l => ({
        cuenta_id:      l.cuenta_id,
        cuenta_codigo:  l.cuenta_codigo,
        cuenta_nombre:  l.cuenta_nombre,
        tercero_id:     l.tercero_id,
        tercero_nombre: l.tercero_nombre || '',
        debito:         parseFloat(l.debito),
        credito:        parseFloat(l.credito),
        descripcion:    l.descripcion || '',
        doc_cruce_tipo: l.doc_cruce_tipo || '',
        doc_cruce_num:  l.doc_cruce_num  || '',
        vencimiento:    l.vencimiento    || '',
    }));

    renderGrid();
    actualizarTotales();
    document.getElementById('status-msg').textContent = `Comprobante #${id} – Estado: ${data.estado}`;
}

// ─── Keyboard navigation en el grid ─────────────────────────────────────
function handleGridKey(e, idx, col) {
    if (e.key === 'Tab' || e.key === 'Enter') {
        e.preventDefault();
        // Mover al campo débito de la misma fila
        document.getElementById(`inp-deb-${idx}`)?.focus();
    }
    if (e.key === 'F8') { e.preventDefault(); addLinea(); }
}

// ─── Helpers ─────────────────────────────────────────────────────────────
function fmt(v) {
    return new Intl.NumberFormat('es-HN', { style:'currency', currency:'HNL', minimumFractionDigits:2 }).format(v||0);
}
function esc(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
</body>
</html>
