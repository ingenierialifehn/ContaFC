<?php
/**
 * Sidebar Premium – ContaFC Multi-Company 2026.
 * AUTOSUFICIENTE: No depende de $user/$empresa del scope externo.
 * Obtiene siempre los datos frescos desde Auth y la DB.
 */
$b = BASE_URL;

// ── Obtener usuario fresco (Auth hidrata 'empresas' automáticamente) ──────
$_sidebarUser = \ContaFC\Core\Auth::user() ?? [];

// ── Obtener empresa activa desde DB (no de variable local) ────────────────
$_sidebarEmpresa = $empresa ?? null;
if (empty($_sidebarEmpresa)) {
    try {
        $_pdo = \ContaFC\Core\Database::getInstance()->getPdo();
        $_sidebarEmpresa = $_pdo
            ->query("SELECT * FROM empresas WHERE id = " . \ContaFC\Core\Auth::empresaId())
            ->fetch();
    } catch (\Throwable $_e) {
        $_sidebarEmpresa = [];
    }
}

// ── Alias para el template ────────────────────────────────────────────────
$empNombre = htmlspecialchars($_sidebarEmpresa['nombre'] ?? 'Sin Empresa');
$userName  = htmlspecialchars($_sidebarUser['nombre'] ?? '');
$userRol   = htmlspecialchars($_sidebarUser['rol'] ?? '');
$userIni   = strtoupper(substr($_sidebarUser['nombre'] ?? 'A', 0, 1));
$user      = $_sidebarUser;   // Aseguramos que $user siempre esté disponible
$activeNav = $activeNav ?? '';

// Icons – únicos por función
$iDash      = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>';
$iChart     = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>';
$iPOS       = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>';
$iFactura   = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
$iCart      = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>';
$iLogistic  = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>';
$iContratos = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>';
$iDevol     = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>';
$iAsiento   = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>';
$iComprobante = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>';
$iActivos   = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>';
$iBancos    = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l9-4 9 4M3 10h18M3 18h18M5 10v8m4-8v8m4-8v8m4-8v8"/></svg>';
$iRecurrente = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
$iCecos     = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>';
$iAuditoria = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>';
$iCert      = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>';
$iLibros    = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>';
$iPUC       = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>';
$iTerceros  = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
$iCartera   = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>';
$iUsuarios  = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>';
$iCAI       = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>';
$iEmpresas  = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>';
$iProyectos = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>';
$iBackups   = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>';

// 📋 Estructura de Navegación por Categorías
$MENU = [
    'General' => [
        ['dashboard',   'dashboard.php',              $iDash,       'Resumen Global'],
        ['reportes',    'reportes.php',               $iChart,      'Reportes & Balances'],
    ],
    'Ecosistema Comercial' => [
        ['pos',         'pos.php',                    $iPOS,        'Punto de Venta (POS)'],
        ['factura',     'factura.php',                $iFactura,    'Facturación SAR'],
        ['productos',   'productos.php',              $iCart,       'Inventario y Kits'],
        ['logistica',   'logistica.php',              $iLogistic,   'Logística y Envíos'],
        ['contratos',   'contratos.php',              $iContratos,  'Fact. Recurrente'],
        ['devoluciones','devolucion.php',             $iDevol,      'Notas de Crédito'],
    ],
    'Contabilidad Core' => [
        ['asiento',     'asiento.php',                $iAsiento,    'Asientos de Diario'],
        ['comprobantes','comprobantes.php',           $iComprobante,'Comprobantes'],
        ['activos',     'activos.php',                $iActivos,    'Activos Fijos'],
        ['tesoreria',   'tesoreria_bancos.php',       $iBancos,     'Bancos y Tesorería'],
        ['recurrente',  'tesoreria_recurrentes.php',  $iRecurrente, 'Egreso Recurrente'],
        ['cecos',       'cecos.php',                  $iCecos,      'Centros de Costo'],
        ['auditoria',   'auditoria.php',              $iAuditoria,  'Auditoría & Logs'],
        ['certificados','certificados_hnd.php',       $iCert,       'Certificados SAR'],
        ['libros',      'libros_oficiales.php',       $iLibros,     'Libros Oficiales'],
        ['puc',         'puc.php',                    $iPUC,        'Plan de Cuentas'],
        ['terceros',    'terceros.php',               $iTerceros,   'Clientes y Prov.'],
    ],
    'Cartera y Cobros' => [
        ['cartera',     'cartera.php',                $iCartera,    'Créditos y Recaudos'],
    ],
    'Administración' => [
        ['usuarios',    'usuarios.php',               $iUsuarios,   'Usuarios'],
        ['cai',         'cai.php',                    $iCAI,        'Resoluciones SAR'],
        ['empresas',    'empresas.php',               $iEmpresas,   'Ajustes Multiempresa'],
        ['proyectos',   'proyectos.php',              $iProyectos,  'Gestión de Proyectos'],
        ['backups',     'backups.php',                $iBackups,    'Copias de Seguridad'],
    ]
];

if (!function_exists('navLink')) {
    function navLink(string $href, string $icon, string $label, string $active, string $key): string {
        // SEGURIDAD: Si no tiene permiso, no renderizar nada
        if (!\ContaFC\Core\Auth::canAccess($key, 'r')) return "";

        $isActive = ($active === $key);
        $baseCls  = 'flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-300 text-[13px] font-bold tracking-tight relative group';
        
        $cls      = $isActive 
            ? $baseCls . ' bg-white/5 text-sky-400 shadow-sm shadow-black/20 border border-white/5'
            : $baseCls . ' text-slate-500 hover:bg-white/[0.03] hover:text-slate-200 border border-transparent';
            
        $iconClass = $isActive 
            ? "text-sky-400 drop-shadow-[0_0_8px_rgba(14,165,233,0.5)]"
            : "text-slate-600 group-hover:text-slate-300 transition-colors";
        
        return "
        <a href=\"{$href}\" data-nav-key=\"{$key}\" class=\"{$cls}\">
            <span class=\"flex-shrink-0 {$iconClass}\">{$icon}</span>
            <span class=\"truncate\">{$label}</span>
            " . ($isActive ? '<div class="absolute right-3 w-1.5 h-1.5 rounded-full bg-sky-500 shadow-[0_0_10px_#0ea5e9]"></div>' : '') . "
        </a>";
    }
}
?>
<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .sidebar-bg { background: linear-gradient(180deg, #020617 0%, #0f172a 100%); }
    #spa-loader {
        position: fixed; top: 0; left: 288px; right: 0; height: 2px; z-index: 9999;
        background: linear-gradient(90deg, #0ea5e9, #10b981, #0ea5e9);
        background-size: 200% 100%;
        animation: spa-slide 1s linear infinite;
    }
    @keyframes spa-slide { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
</style>

<!-- SPA Global Loader -->
<div id="spa-loader" class="hidden"></div>

<aside class="w-72 min-h-screen flex flex-col items-stretch sidebar-bg border-r border-white/[0.05] relative z-20">
    
    <!-- Header: App & Company -->
    <div class="px-8 py-10 flex flex-col gap-6">
        <div class="flex items-center gap-3">
             <div class="w-10 h-10 bg-sky-500 rounded-2xl flex items-center justify-center shadow-[0_0_20px_rgba(14,165,233,0.3)]">
                <svg class="w-6 h-6 text-slate-950" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/></svg>
            </div>
            <span class="text-2xl font-black text-white tracking-tighter italic">Conta<span class="text-sky-500">FC</span></span>
        </div>

        <div class="relative group" id="company-dropdown-container">
            <button type="button" onclick="toggleCompanyDropdown()" 
                 class="w-full p-4 bg-white/[0.03] border border-white/[0.05] rounded-3xl group hover:border-sky-500/30 transition-all text-left relative overflow-hidden">
                <div class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1 flex justify-between items-center relative z-10">
                    Entidad Actual
                    <svg class="w-3 h-3 text-sky-500 transition-transform duration-300" id="dd-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                </div>
                <div class="text-slate-200 font-bold text-xs truncate drop-shadow-sm uppercase tracking-tight relative z-10">
                    <?= $empNombre ?>
                </div>
                <!-- Background Glow -->
                <div class="absolute -right-4 -bottom-4 w-12 h-12 bg-sky-500/5 rounded-full blur-xl group-hover:bg-sky-500/10 transition"></div>
            </button>

            <!-- Custom Dropdown Menu -->
            <div id="company-dropdown-menu" 
                 class="absolute left-0 mt-3 w-full bg-[#0f172a]/95 backdrop-blur-xl border border-white/10 rounded-3xl shadow-2xl overflow-hidden z-[100] scale-95 opacity-0 invisible transition-all duration-300 origin-top">
                <div class="p-2 max-h-[300px] overflow-y-auto no-scrollbar">
                    <?php foreach ($user['empresas'] ?? [] as $emp): 
                        $isSel = (int)$emp['id'] === (int)(\ContaFC\Core\Auth::empresaId());
                    ?>
                        <button type="button" onclick="switchCompanyDirect(<?= $emp['id'] ?>)" 
                                class="w-full p-4 rounded-2xl flex items-center justify-between text-left transition-all border border-transparent 
                                       <?= $isSel ? 'bg-sky-500/10 text-sky-400 border-sky-500/10' : 'text-slate-400 hover:bg-white/5 hover:text-white' ?>">
                            <span class="text-[11px] font-black uppercase tracking-tight truncate"><?= htmlspecialchars($emp['nombre']) ?></span>
                            <?php if ($isSel): ?>
                                <svg class="w-3 h-3 text-sky-500" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
                            <?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="p-3 bg-black/20 border-t border-white/5">
                     <a href="<?= $b ?>/empresas.php" class="block py-2 text-center text-[9px] font-black text-slate-500 hover:text-sky-500 uppercase tracking-widest transition">Gestionar Empresas →</a>
                </div>
            </div>
        </div>

        <!-- Hidden Switch Form -->
        <form action="<?= $b ?>/api/switch_company.php" method="POST" id="hidden-switch-form" class="hidden">
            <input type="hidden" name="empresa_id" id="hidden-empresa-id">
        </form>

        <script>
            function toggleCompanyDropdown() {
                const menu = document.getElementById('company-dropdown-menu');
                const arrow = document.getElementById('dd-arrow');
                const isOpen = !menu.classList.contains('opacity-0');

                if (isOpen) {
                    menu.classList.add('opacity-0', 'scale-95', 'invisible');
                    arrow.classList.remove('rotate-180');
                } else {
                    menu.classList.remove('opacity-0', 'scale-95', 'invisible');
                    arrow.classList.add('rotate-180');
                }
            }

            function switchCompanyDirect(id) {
                document.getElementById('hidden-empresa-id').value = id;
                document.getElementById('hidden-switch-form').submit();
            }

            // ══════════════════════════════════════════════════════════
            //  Motor SPA – Navegación sin recarga de página
            // ══════════════════════════════════════════════════════════

            // Cerrar dropdown al hacer click fuera
            document.addEventListener('click', (e) => {
                if (!document.getElementById('company-dropdown-container').contains(e.target)) {
                    const menu = document.getElementById('company-dropdown-menu');
                    if (menu && !menu.classList.contains('opacity-0')) {
                        menu.classList.add('opacity-0', 'scale-95', 'invisible');
                        document.getElementById('dd-arrow').classList.remove('rotate-180');
                    }
                }
            });

            function spaNavigate(url, pushState = true) {
                const nav    = document.getElementById('sidebar-nav');
                const mainEl = document.querySelector('main');
                const loader = document.getElementById('spa-loader');

                if (!mainEl) { window.location = url; return; }

                if (nav) sessionStorage.setItem('contafc_sidebar_scroll', nav.scrollTop);

                if (loader) loader.classList.remove('hidden');
                mainEl.style.opacity    = '0.35';
                mainEl.style.transition = 'opacity .12s';

                fetch(url)
                    .then(r => r.text())
                    .then(html => {
                        const parser   = new DOMParser();
                        const doc      = parser.parseFromString(html, 'text/html');
                        const newMain  = doc.querySelector('main');
                        const newTitle = doc.querySelector('title')?.textContent || '';

                        if (!newMain) { window.location = url; return; }

                        mainEl.innerHTML = newMain.innerHTML;
                        mainEl.className = newMain.className;
                        mainEl.style.opacity = '1';
                        if (newTitle) document.title = newTitle;

                        if (pushState) history.pushState({ spaUrl: url }, newTitle, url);

                        const pageKey = url.split('/').pop().replace('.php', '').split('?')[0];
                        document.querySelectorAll('#sidebar-nav a[data-nav-key]').forEach(a => {
                            const active = a.dataset.navKey === pageKey;
                            a.classList.toggle('bg-white/5',          active);
                            a.classList.toggle('text-sky-400',         active);
                            a.classList.toggle('shadow-sm',            active);
                            a.classList.toggle('border-white/5',       active);
                            a.classList.toggle('text-slate-500',       !active);
                            a.classList.toggle('border-transparent',   !active);
                        });

                        mainEl.querySelectorAll('script').forEach(old => {
                            const s = document.createElement('script');
                            if (old.src) { s.src = old.src; s.async = false; }
                            else s.textContent = old.textContent;
                            document.body.appendChild(s).parentNode.removeChild(s);
                        });

                        mainEl.scrollTop = 0;
                    })
                    .catch(() => { window.location = url; })
                    .finally(() => {
                        if (loader) loader.classList.add('hidden');
                        if (nav) {
                            const saved = sessionStorage.getItem('contafc_sidebar_scroll');
                            if (saved) nav.scrollTop = parseInt(saved, 10);
                        }
                    });
            }

            document.getElementById('sidebar-nav')?.addEventListener('click', (e) => {
                const link = e.target.closest('a[href]');
                if (!link) return;
                const href = link.getAttribute('href') || '';
                if (href && !href.includes('logout') && !href.startsWith('http') && !href.startsWith('#')) {
                    e.preventDefault();
                    spaNavigate(href);
                }
            });

            window.addEventListener('popstate', (e) => {
                const target = e.state?.spaUrl || window.location.href;
                spaNavigate(target, false);
            });

            history.replaceState({ spaUrl: window.location.href }, document.title, window.location.href);

            (() => {
                const nav   = document.getElementById('sidebar-nav');
                const saved = sessionStorage.getItem('contafc_sidebar_scroll');
                if (nav && saved) nav.scrollTop = parseInt(saved, 10);
            })();
        </script>
    </div>

    <!-- Navigation -->
    <nav id="sidebar-nav" class="flex-1 px-4 overflow-y-auto no-scrollbar space-y-6 pb-10">
        
        <?php foreach ($MENU as $cat => $items): 
            $visibleItems = array_filter($items, fn($i) => \ContaFC\Core\Auth::canAccess($i[0], 'r'));
            if (empty($visibleItems)) continue;
        ?>
        <div>
            <p class="px-4 text-[10px] font-black text-slate-600 uppercase tracking-[0.2em] mb-3 ml-1"><?= $cat ?></p>
            <div class="space-y-1">
                <?php foreach ($visibleItems as $i): ?>
                    <?= navLink("$b/{$i[1]}", $i[2], $i[3], $activeNav, $i[0]) ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

    </nav>

    <!-- Footer: User & Logout -->
    <div class="p-6 bg-black/20 border-t border-white/[0.03]">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-10 h-10 rounded-2xl bg-sky-500 text-slate-950 flex items-center justify-center font-black text-sm shadow-lg shadow-sky-500/10">
                <?= $userIni ?>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-[13px] font-black text-white tracking-tight truncate"><?= $userName ?></div>
                <div class="text-[10px] font-bold text-sky-500 uppercase tracking-widest opacity-80"><?= $userRol ?></div>
            </div>
        </div>
        
        <a href="<?= $b ?>/logout.php" 
           class="flex items-center justify-center gap-2 w-full h-11 rounded-2xl bg-rose-500/10 text-rose-500 font-black text-[10px] uppercase tracking-widest hover:bg-rose-500 hover:text-white transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Finalizar Sesión
        </a>
    </div>

</aside>
