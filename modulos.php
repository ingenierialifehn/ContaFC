<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requireRol('admin');

$b          = BASE_URL;
$activeNav  = 'modulos';
$pageTitle  = 'Gestión de Módulos';
$empresa    = null;
try {
    $pdo    = Database::getInstance()->getPdo();
    $stmt   = $pdo->prepare("SELECT * FROM empresas WHERE id = :id");
    $stmt->execute([':id' => Auth::empresaId()]);
    $empresa = $stmt->fetch();
} catch (\Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestión de Módulos – ContaFC</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
  * { font-family: 'Inter', sans-serif; }
  body { background: #020617; }

  .module-card {
    background: linear-gradient(135deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 24px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
  }
  .module-card::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 24px;
    opacity: 0;
    transition: opacity 0.3s ease;
  }
  .module-card:hover { border-color: rgba(255,255,255,0.12); transform: translateY(-2px); }

  .module-card.active-card { border-color: rgba(14,165,233,0.2); background: linear-gradient(135deg, rgba(14,165,233,0.06) 0%, rgba(14,165,233,0.02) 100%); }
  .module-card.active-card:hover { border-color: rgba(14,165,233,0.35); }

  /* Toggle Switch */
  .toggle-bg {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.08);
    transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
  }
  .toggle-bg.on { background: #0ea5e9; border-color: #0ea5e9; box-shadow: 0 0 20px rgba(14,165,233,0.4); }
  .toggle-knob {
    background: rgba(255,255,255,0.4);
    transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
    transform: translateX(0);
  }
  .toggle-bg.on .toggle-knob { background: #fff; transform: translateX(24px); box-shadow: 0 2px 8px rgba(0,0,0,0.3); }

  .badge-active   { background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.2); }
  .badge-inactive { background: rgba(255,255,255,0.04); color: #475569; border: 1px solid rgba(255,255,255,0.06); }

  .glow-btn {
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    box-shadow: 0 4px 20px rgba(14,165,233,0.35);
    transition: all 0.3s ease;
  }
  .glow-btn:hover { box-shadow: 0 8px 30px rgba(14,165,233,0.55); transform: translateY(-1px); }
  .glow-btn:active { transform: translateY(0); }

  @keyframes fadeInUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
  .card-anim { animation: fadeInUp 0.4s ease both; }
  .card-anim:nth-child(1) { animation-delay: 0.05s; }
  .card-anim:nth-child(2) { animation-delay: 0.10s; }
  .card-anim:nth-child(3) { animation-delay: 0.15s; }
  .card-anim:nth-child(4) { animation-delay: 0.20s; }
  .card-anim:nth-child(5) { animation-delay: 0.25s; }

  #toast {
    position: fixed; bottom: 32px; right: 32px;
    padding: 14px 24px; border-radius: 16px;
    font-size: 13px; font-weight: 700;
    display: none; z-index: 9999;
    backdrop-filter: blur(20px);
    animation: fadeInUp 0.3s ease;
  }
  #toast.success { background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); color: #10b981; }
  #toast.error   { background: rgba(239,68,68,0.15);  border: 1px solid rgba(239,68,68,0.3);  color: #ef4444; }
</style>
</head>
<body class="flex min-h-screen">

<?php require_once __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-1 overflow-y-auto p-10">

  <!-- Header -->
  <div class="mb-10">
    <div class="flex items-center gap-4 mb-3">
      <div class="w-12 h-12 rounded-2xl flex items-center justify-center" style="background: rgba(14,165,233,0.1); border: 1px solid rgba(14,165,233,0.2);">
        <svg class="w-6 h-6 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
        </svg>
      </div>
      <div>
        <h1 class="text-3xl font-black text-white tracking-tight">Gestión de Módulos</h1>
        <p class="text-slate-500 text-sm mt-0.5">Activa o desactiva los módulos del sistema para esta empresa.</p>
      </div>
    </div>

    <!-- Info banner -->
    <div class="mt-6 p-4 rounded-2xl flex items-start gap-3" style="background: rgba(245,158,11,0.06); border: 1px solid rgba(245,158,11,0.15);">
      <svg class="w-5 h-5 text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <p class="text-amber-300/80 text-xs font-medium leading-relaxed">
        Los módulos desactivados <strong>no aparecerán en el menú lateral</strong>. Los datos existentes no se eliminan. Puedes reactivarlos en cualquier momento.
        Los cambios aplican solo a la empresa activa: <strong id="empresa-nombre" class="text-amber-300"><?= htmlspecialchars($empresa['nombre'] ?? '—') ?></strong>.
      </p>
    </div>
  </div>

  <!-- Grid de módulos -->
  <div id="modules-grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-10">
    <!-- Se llena vía JS -->
    <div class="col-span-full flex items-center justify-center py-16">
      <div class="flex flex-col items-center gap-3 text-slate-600">
        <svg class="w-8 h-8 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        <span class="text-sm font-medium">Cargando módulos...</span>
      </div>
    </div>
  </div>

  <!-- Botón guardar -->
  <div class="flex items-center justify-between">
    <div id="pending-info" class="hidden flex items-center gap-2 text-amber-400 text-sm font-semibold">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      Hay cambios sin guardar
    </div>
    <div class="ml-auto">
      <button id="btn-guardar" onclick="guardarModulos()"
        class="glow-btn h-12 px-8 rounded-2xl text-white text-sm font-black uppercase tracking-widest flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        Guardar Cambios
      </button>
    </div>
  </div>

</main>

<!-- Toast -->
<div id="toast"></div>

<script>
const API = '<?= $b ?>/api/modulos.php';
let modulosData = [];
let pendingChanges = false;

// Íconos SVG por color
function iconSvg(path, color) {
  return `<svg class="w-7 h-7" fill="none" stroke="${color}" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="${path}"/>
  </svg>`;
}

async function cargarModulos() {
  let rawText = '';
  try {
    const res = await fetch(API, { credentials: 'same-origin' });
    rawText = await res.text();
    if (!rawText.trim().startsWith('{') && !rawText.trim().startsWith('[')) {
      throw new Error('La API no devolvió JSON. Vista previa: ' + rawText.substring(0, 200));
    }
    const json = JSON.parse(rawText);
    if (!json.success) throw new Error(json.error || 'Error al cargar');
    modulosData = json.data;
    renderModulos();
  } catch(e) {
    document.getElementById('modules-grid').innerHTML = `
      <div class="col-span-full py-10 px-6">
        <p class="text-red-400 text-sm font-bold mb-2">Error al cargar módulos:</p>
        <pre class="text-red-300/70 text-xs bg-red-500/5 border border-red-500/10 rounded-xl p-4 overflow-x-auto whitespace-pre-wrap">${escHtml(e.message)}</pre>
      </div>`;
  }
}

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function renderModulos() {
  const grid = document.getElementById('modules-grid');
  grid.innerHTML = '';

  modulosData.forEach((m, idx) => {
    const isActive = m.activo;
    const card = document.createElement('div');
    card.className = `module-card card-anim p-6 cursor-pointer select-none ${isActive ? 'active-card' : ''}`;
    card.dataset.key = m.key;
    card.style.animationDelay = (idx * 0.05) + 's';

    card.innerHTML = `
      <div class="flex items-start justify-between mb-5">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center flex-shrink-0"
             style="background: ${isActive ? m.color + '22' : 'rgba(255,255,255,0.04)'}; border: 1px solid ${isActive ? m.color + '44' : 'rgba(255,255,255,0.06)'};">
          ${iconSvg(m.icono, isActive ? m.color : '#475569')}
        </div>
        <!-- Toggle -->
        <div class="toggle-bg w-12 h-6 rounded-full relative flex-shrink-0 mt-1 ${isActive ? 'on' : ''}" 
             id="toggle-${m.key}" onclick="event.stopPropagation(); toggleModulo('${m.key}')">
          <div class="toggle-knob absolute top-1 left-1 w-4 h-4 rounded-full"></div>
        </div>
      </div>
      <div class="mb-3">
        <div class="flex items-center gap-2 mb-1">
          <h3 class="text-white font-black text-base tracking-tight">${m.nombre}</h3>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider ${isActive ? 'badge-active' : 'badge-inactive'}" id="badge-${m.key}">
            ${isActive ? 'Activo' : 'Inactivo'}
          </span>
        </div>
        <p class="text-slate-500 text-xs leading-relaxed font-medium">${m.descripcion}</p>
      </div>
      <div class="mt-4 pt-4 border-t border-white/5 flex items-center gap-2">
        <div class="w-1.5 h-1.5 rounded-full ${isActive ? 'bg-emerald-500 shadow-[0_0_8px_#10b981]' : 'bg-slate-700'}"></div>
        <span class="text-[11px] font-bold ${isActive ? 'text-emerald-400' : 'text-slate-600'}" id="status-text-${m.key}">
          ${isActive ? 'Visible en el menú lateral' : 'Oculto del menú lateral'}
        </span>
      </div>
    `;

    card.addEventListener('click', () => toggleModulo(m.key));
    grid.appendChild(card);
  });
}

function toggleModulo(key) {
  const idx = modulosData.findIndex(m => m.key === key);
  if (idx < 0) return;
  modulosData[idx].activo = !modulosData[idx].activo;
  pendingChanges = true;
  document.getElementById('pending-info')?.classList.remove('hidden');
  renderModulos();
}

async function guardarModulos() {
  const btn = document.getElementById('btn-guardar');
  btn.disabled = true;
  btn.innerHTML = `<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Guardando...`;

  const modulos = {};
  modulosData.forEach(m => { modulos[m.key] = m.activo; });

  try {
    const res = await fetch(API, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ modulos })
    });
    const rawText = await res.text();
    let json;
    try { json = JSON.parse(rawText); }
    catch(pe) { throw new Error('API no devolvió JSON: ' + rawText.substring(0, 200)); }
    if (!json.success) throw new Error(json.error || 'Error al guardar');

    pendingChanges = false;
    document.getElementById('pending-info')?.classList.add('hidden');
    showToast('✓ ' + json.message, 'success');

    // Recargar sidebar después de un momento
    setTimeout(() => window.location.reload(), 1200);

  } catch(e) {
    showToast('✗ ' + e.message, 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Guardar Cambios`;
  }
}

function showToast(msg, type) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = type;
  t.style.display = 'block';
  setTimeout(() => { t.style.display = 'none'; }, 3500);
}

// Advertir si hay cambios sin guardar
window.addEventListener('beforeunload', e => {
  if (pendingChanges) { e.preventDefault(); e.returnValue = ''; }
});

cargarModulos();
</script>
</body>
</html>
