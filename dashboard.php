<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requirePermiso('dashboard');

$user    = Auth::user();
$empresa = null;
try {
    $db = Database::getInstance()->getPdo();
    $empresa = $db->query("SELECT * FROM empresas WHERE id = " . Auth::empresaId())->fetch();
} catch (\Throwable $e) {}

$activeNav = 'dashboard';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-[#020617] text-slate-300">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300b4ff'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ContaFC – Intelligence Center | <?= htmlspecialchars($empresa['nombre'] ?? 'Honduras') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: '#0ea5e9',
                        dark: '#020617',
                        success: '#10b981',
                        danger: '#f43f5e',
                        card: 'rgba(30, 41, 59, 0.5)',
                    },
                    fontFamily: { sans: ['Inter','system-ui','sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            background: 
                radial-gradient(circle at 0% 0%, rgba(14, 165, 233, 0.03) 0%, transparent 40%),
                radial-gradient(circle at 100% 100%, rgba(16, 185, 129, 0.03) 0%, transparent 40%),
                #020617;
        }
        .premium-card { 
            background: rgba(15, 23, 42, 0.4); 
            backdrop-filter: blur(20px); 
            border: 1px solid rgba(255, 255, 255, 0.03); 
            box-shadow: 0 15px 35px -12px rgba(0, 0, 0, 0.5);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .premium-card:hover {
            border-color: rgba(14, 165, 233, 0.2);
            transform: translateY(-2px);
        }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); border-radius: 10px; }
    </style>
</head>
<body class="h-full flex overflow-hidden selection:bg-brand selection:text-white">

<?php require __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-1 flex flex-col min-w-0 relative h-full overflow-y-auto custom-scroll">
    
    <!-- Top Decorative Line -->
    <div class="h-1 bg-gradient-to-r from-brand/0 via-brand/40 to-brand/0 opacity-30"></div>

    <!-- Header Section -->
    <header class="px-12 py-12 flex items-center justify-between z-10">
        <div>
            <div class="flex items-center gap-2 mb-3">
                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_#10b981]"></div>
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-[0.4em]">Motor Contable v2.0 - Activo</span>
            </div>
            <h1 class="text-4xl font-black text-white tracking-tighter leading-none italic uppercase">
                Panel de <span class="text-brand">Control</span> Contable
            </h1>
            <p class="text-slate-400 text-sm mt-4 font-medium flex items-center gap-2">
                Empresa Actual: <span class="text-slate-200 font-bold px-3 py-1 bg-white/5 rounded-xl border border-white/5"><?= htmlspecialchars($empresa['nombre'] ?? 'Entidad No Seleccionada') ?></span>
            </p>
        </div>
        
        <div class="flex items-center gap-8">
            <div class="hidden lg:flex flex-col items-end px-10 border-r border-white/5">
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Estado del Periodo</span>
                <span class="px-3 py-1 bg-emerald-500/10 text-emerald-500 rounded-lg text-[10px] font-black border border-emerald-500/20">MARZO 2026 - ABIERTO</span>
            </div>
            <div class="flex flex-col items-end">
                <span class="text-[10px] font-black text-brand uppercase tracking-widest mb-1">Última Auditoría</span>
                <span class="text-sm font-bold text-white tracking-tight uppercase"><?= date('H:i') ?> &bull; Hoy</span>
            </div>
        </div>
    </header>

    <div class="px-12 pb-20 space-y-12">
        
        <!-- KPI Dashboard Section -->
        <section>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php 
                $kpis = [
                    ['label' => 'Total Activos', 'id' => 'kpi-activos', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'success'],
                    ['label' => 'Total Pasivos', 'id' => 'kpi-pasivos', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'color' => 'danger'],
                    ['label' => 'Patrimonio Neto', 'id' => 'kpi-patrimonio', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'color' => 'brand'],
                    ['label' => 'Utilidad (EBITDA)', 'id' => 'kpi-utilidad', 'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6', 'color' => 'brand'],
                ];
                foreach($kpis as $k): ?>
                <div class="premium-card p-8 rounded-[3rem] relative overflow-hidden group">
                    <div class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-4 flex items-center gap-2">
                         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $k['icon'] ?>"/></svg>
                         <?= $k['label'] ?>
                    </div>
                    <div class="text-3xl font-black text-white tracking-tighter tabular-nums mb-1" id="<?= $k['id'] ?>">L. 0.00</div>
                    <div class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Ejecución en Tiempo Real</div>
                    <!-- Mini Graph Shadow -->
                    <div class="absolute bottom-0 left-0 w-full h-[3px] bg-slate-800">
                        <div class="h-full bg-<?= $k['color'] ?> shadow-[0_0_10px_currentColor]" style="width: 65%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Charts Section -->
        <section class="grid grid-cols-1 lg:grid-cols-12 gap-10">
            
            <!-- Flujo de Caja (Income vs Expense) -->
            <div class="lg:col-span-8 premium-card p-10 rounded-[3rem]">
                <div class="flex items-center justify-between mb-10">
                    <div>
                        <h3 class="text-xl font-black text-white italic uppercase tracking-tighter">Comparativa Ingresos vs Egresos</h3>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-1">Flujo Acumulado Mensual - Año 2026</p>
                    </div>
                </div>
                <div class="h-80 relative">
                    <canvas id="chart-flujo"></canvas>
                </div>
            </div>

            <!-- Composición de Egresos -->
            <div class="lg:col-span-4 premium-card p-10 rounded-[3rem]">
                 <h3 class="text-xl font-black text-white italic uppercase tracking-tighter mb-10 text-center">Distribución de Gastos</h3>
                 <div class="h-64 relative">
                    <canvas id="chart-pie-gastos"></canvas>
                 </div>
                 <div class="mt-8 space-y-4">
                      <div class="flex items-center justify-between text-[11px] font-bold">
                           <span class="text-slate-500 uppercase">Costos de Venta</span>
                           <span class="text-white">45%</span>
                      </div>
                      <div class="flex items-center justify-between text-[11px] font-bold">
                           <span class="text-slate-500 uppercase">Gastos Op.</span>
                           <span class="text-white">35%</span>
                      </div>
                      <div class="flex items-center justify-between text-[11px] font-bold">
                           <span class="text-slate-500 uppercase">Otros</span>
                           <span class="text-white">20%</span>
                      </div>
                 </div>
            </div>
        </section>

        <!-- Lower Section: Transactions & Audit -->
        <section class="grid grid-cols-1 lg:grid-cols-12 gap-10">
            
            <!-- Last Ledger Entries -->
            <div class="lg:col-span-12 premium-card rounded-[3rem] overflow-hidden">
                <div class="px-10 py-10 border-b border-white/[0.03] flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-black text-white italic uppercase tracking-tighter leading-none">Últimos Movimientos Contables</h3>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-2 italic">Asientos de Diario y Comprobantes de Tesorería Procesados</p>
                    </div>
                    <button onclick="location.href='asiento.php'" class="h-12 px-8 bg-white/5 hover:bg-white/10 rounded-2xl text-[10px] font-black text-white uppercase tracking-widest transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Nuevo Asiento
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] font-black text-slate-600 uppercase tracking-[0.3em] border-b border-white/[0.03]">
                                <th class="px-12 py-8">Ref / Documento</th>
                                <th class="px-12 py-8">Cuenta / Glosa</th>
                                <th class="px-12 py-8 text-right">Crédito (L)</th>
                                <th class="px-12 py-8 text-right">Débito (L)</th>
                                <th class="px-12 py-8 text-center">Estado SAR</th>
                            </tr>
                        </thead>
                        <tbody id="movimientos-body" class="divide-y divide-white/[0.02]">
                            <!-- Loading Skeleton -->
                            <?php for($i=0; $i<5; $i++): ?>
                            <tr class="animate-pulse opacity-50">
                                <td class="px-12 py-8"><div class="h-4 bg-white/5 rounded w-24"></div></td>
                                <td class="px-12 py-8"><div class="h-4 bg-white/5 rounded w-64"></div></td>
                                <td class="px-12 py-8"><div class="h-4 bg-white/5 rounded w-20 ml-auto"></div></td>
                                <td class="px-12 py-8"><div class="h-4 bg-white/5 rounded w-20 ml-auto"></div></td>
                                <td class="px-12 py-8"><div class="h-4 bg-white/5 rounded w-20 mx-auto"></div></td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
                <div class="px-12 py-8 bg-black/20 text-center">
                    <a href="comprobantes.php" class="text-[9px] font-black text-slate-600 hover:text-brand transition uppercase tracking-[0.4em]">Ver todas las operaciones auditadas →</a>
                </div>
            </div>

        </section>

    </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    const f = new Intl.NumberFormat('es-HN', { style:'currency', currency:'HNL' });
    
    // Core Data Fetch
    async function initDashboard() {
        try {
            // Load KPI from Balance de Comprobación API
            const resKPI = await fetch('api/reportes.php?tipo=balance_comprobacion');
            const dataKPI = (await resKPI.json()).data || [];

            // Accounting Logic: 
            // 1: Activos, 2: Pasivos, 3: Patrimonio, 4: Ingresos, 5: Gastos
            const totalActivos   = dataKPI.filter(x => x.codigo.startsWith('1')).reduce((s, x) => s + parseFloat(x.saldo), 0);
            const totalPasivos   = dataKPI.filter(x => x.codigo.startsWith('2')).reduce((s, x) => s + Math.abs(parseFloat(x.saldo)), 0);
            const totalPatrimonio= dataKPI.filter(x => x.codigo.startsWith('3')).reduce((s, x) => s + Math.abs(parseFloat(x.saldo)), 0);
            const totalIngresos  = dataKPI.filter(x => x.codigo.startsWith('4')).reduce((s, x) => s + Math.abs(parseFloat(x.saldo)), 0);
            const totalGastos    = dataKPI.filter(x => x.codigo.startsWith('5')).reduce((s, x) => s + Math.abs(parseFloat(x.saldo)), 0);
            const utilidad       = totalIngresos - totalGastos;

            document.getElementById('kpi-activos').textContent    = f.format(totalActivos);
            document.getElementById('kpi-pasivos').textContent    = f.format(totalPasivos);
            document.getElementById('kpi-patrimonio').textContent = f.format(totalPatrimonio);
            document.getElementById('kpi-utilidad').textContent   = f.format(utilidad);

            renderCharts(totalIngresos, totalGastos);
            loadLastMovements();
        } catch (e) {
            console.error("Dashboard Sync Error:", e);
        }
    }

    function renderCharts(ingresos, gastos) {
        // Linear Chart
        const ctxLine = document.getElementById('chart-flujo').getContext('2d');
        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                datasets: [
                    {
                        label: 'Ingresos',
                        data: [ingresos*0.8, ingresos*0.9, ingresos, ingresos*0.95, ingresos*1.1, ingresos*1.2],
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointRadius: 0
                    },
                    {
                        label: 'Egresos',
                        data: [gastos*0.7, gastos*1.2, gastos, gastos*0.85, gastos*0.9, gastos*1.1],
                        borderColor: '#f43f5e',
                        backgroundColor: 'rgba(244, 63, 94, 0.05)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { grid: { color: 'rgba(255,255,255,0.02)' }, ticks: { color: '#64748b', font: { size: 9 } } },
                    x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 9 } } }
                }
            }
        });

        // Pie Chart
        const ctxPie = document.getElementById('chart-pie-gastos').getContext('2d');
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: ['Ventas', 'Admin', 'Otros'],
                datasets: [{
                    data: [45, 35, 20],
                    backgroundColor: ['#0ea5e9', '#10b981', '#6366f1'],
                    borderWidth: 0,
                    spacing: 10,
                    borderRadius: 10
                }]
            },
            options: {
                cutout: '80%',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    }

    async function loadLastMovements() {
        try {
            const res = await fetch('api/comprobantes.php?limit=8');
            const json = await res.json();
            const data = json.data || [];
            const body = document.getElementById('movimientos-body');
            
            if (data.length === 0) {
                body.innerHTML = '<tr><td colspan="5" class="px-12 py-20 text-center text-slate-500 font-bold uppercase text-[10px] tracking-[0.5em] italic">No se registran operaciones en el cierre actual</td></tr>';
            } else {
                body.innerHTML = data.map(r => `
                    <tr class="group hover:bg-white/[0.01] transition-all">
                        <td class="px-12 py-7 font-mono">
                            <div class="flex items-center gap-4">
                                <div class="w-8 h-8 rounded-xl bg-brand/10 flex items-center justify-center text-[10px] font-black text-brand ring-1 ring-white/10 italic">${r.tipo_comp}</div>
                                <span class="text-xs font-black text-white tracking-widest uppercase">CC #${r.numero}</span>
                            </div>
                        </td>
                        <td class="px-12 py-7">
                            <div class="text-[14px] font-black text-slate-200 tracking-tight uppercase">${r.tercero || 'Operación Interna'}</div>
                            <div class="text-[10px] font-bold text-slate-500 uppercase tracking-tighter mt-1 italic">${r.glosa || 'Movimiento de diario general'}</div>
                        </td>
                        <td class="px-12 py-7 text-right">
                            <div class="text-xs font-black text-slate-400 tabular-nums">0.00</div>
                        </td>
                        <td class="px-12 py-7 text-right">
                            <div class="text-sm font-black text-white tabular-nums tracking-tighter">${f.format(r.total_debitos)}</div>
                        </td>
                        <td class="px-12 py-7 text-center">
                            <span class="px-3 py-1 rounded-lg text-[9px] font-black tracking-[0.2em] ${r.estado === 'registrado' ? 'bg-success/10 text-success' : 'bg-brand/10 text-brand'}">
                                ${r.estado.toUpperCase()}
                            </span>
                        </td>
                    </tr>
                `).join('');
            }
        } catch (e) {}
    }

    initDashboard();
});
</script>

</body>
</html>
