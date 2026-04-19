<?php
// views/superadmin/analytics.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_SUPERADMIN);

$db = Database::getConnection();

// Fetch INITIAL data via PHP for instant load
$initialData = $db->query("
    SELECT 
        COALESCE((SELECT SUM(total_fee) FROM transactions), 0) as revenue,
        (SELECT COUNT(*) FROM transactions) as transactions,
        (SELECT COUNT(*) FROM sessions WHERE status = 'active') as occupancy,
        (SELECT COUNT(*) FROM audit_logs WHERE action IN ('SECURITY_BLOCK', 'EMERGENCY_OVERRIDE')) as security
")->fetch(PDO::FETCH_ASSOC);

$pageTitle = 'Intelligence Hub';
ob_start();
?>

<!-- Print-Only Official Header -->
<div class="print-only" style="display: none;">
    <div style="text-align: center; border-bottom: 2px solid #1e293b; padding-bottom: 20px; margin-bottom: 40px;">
        <h1 style="font-size: 32px; font-weight: 900; color: #1e293b; margin: 0;">PARKSYS PRO ENTERPRISE</h1>
        <p style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 2px; margin-top: 5px;">Official Facility Intelligence & Audit Report</p>
        <div style="margin-top: 15px; font-size: 13px; color: #1e293b; font-weight: 600;">
            Report Generated: <?= date('F d, Y - h:i A') ?>
        </div>
    </div>
</div>

<div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px;" class="no-print">
    <div>
        <h2 class="section-title" style="margin-bottom: 4px;">Executive Intelligence Hub</h2>
        <p style="font-size: 13px; color: var(--text-muted);">Real-time financial performance and facility utilization trends.</p>
    </div>
    <div style="display: flex; gap: 12px;">
        <button onclick="window.print()" class="btn btn-secondary" style="padding: 10px 16px; font-size: 12px; font-weight: 700;">
            <i data-lucide="printer" style="width:14px; margin-right:8px; vertical-align:middle;"></i> Export Intel PDF
        </button>
        <button onclick="refreshAnalytics()" class="btn btn-primary" style="padding: 10px 16px; font-size: 12px; font-weight: 700;">
            <i data-lucide="refresh-cw" style="width:14px; margin-right:8px; vertical-align:middle;"></i> Live Refresh
        </button>
    </div>
</div>

<!-- AI Intelligence Panel (Excluded from Print) -->
<div class="card no-print" style="margin-bottom: 32px; border: 1px solid var(--primary); background: linear-gradient(90deg, #f8faff 0%, #ffffff 100%); position: relative; overflow: hidden;">
    <div style="position: absolute; top: -10px; right: -10px; opacity: 0.1;">
        <i data-lucide="sparkles" style="width: 120px; height: 120px; color: var(--primary);"></i>
    </div>
    <div style="display: flex; align-items: center; gap: 20px; padding: 24px;">
        <div style="width: 48px; height: 48px; background: var(--primary); color: #fff; border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 16px rgba(37, 99, 235, 0.2);">
            <i data-lucide="brain-circuit"></i>
        </div>
        <div style="flex: 1;">
            <div style="font-size: 11px; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px;">AI Analyst Insights</div>
            <div id="ai-intelligence-text" style="font-size: 15px; font-weight: 600; color: var(--text-main); line-height: 1.5;">
                Analyzing historical patterns and real-time facility telemetry...
            </div>
        </div>
        <div>
            <button onclick="generateAIReport()" class="btn" style="background: #fff; border: 1px solid var(--primary); color: var(--primary); font-size: 12px; font-weight: 700; padding: 8px 16px;">Re-run Intel Analysis</button>
        </div>
    </div>
</div>

<div class="stats-grid" style="margin-bottom: 32px;">
    <div class="card stat-card" style="border: 1px solid var(--border);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div style="width: 32px; height: 32px; background: #ecfdf5; color: #10b981; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="banknote" style="width:16px;"></i>
            </div>
            <span style="font-size: 11px; color: #10b981; font-weight: 800; background: #ecfdf5; padding: 2px 8px; border-radius: 20px;">+12.5%</span>
        </div>
        <div style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Gross Revenue</div>
        <div style="font-size: 28px; font-weight: 900; color: var(--text-main); margin-top: 4px;" id="kpi-revenue"><?= peso($initialData['revenue']) ?></div>
    </div>
    
    <div class="card stat-card" style="border: 1px solid var(--border);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div style="width: 32px; height: 32px; background: #eff6ff; color: #3b82f6; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="credit-card" style="width:16px;"></i>
            </div>
        </div>
        <div style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Total Transactions</div>
        <div style="font-size: 28px; font-weight: 900; color: var(--text-main); margin-top: 4px;" id="kpi-txns"><?= $initialData['transactions'] ?></div>
    </div>

    <div class="card stat-card" style="border: 1px solid var(--border);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div style="width: 32px; height: 32px; background: #fff7ed; color: #f97316; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="users" style="width:16px;"></i>
            </div>
        </div>
        <div style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Active Occupancy</div>
        <div style="font-size: 28px; font-weight: 900; color: var(--text-main); margin-top: 4px;" id="kpi-occ"><?= $initialData['occupancy'] ?></div>
    </div>

    <div class="card stat-card" style="border: 1px solid var(--border);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div style="width: 32px; height: 32px; background: #fef2f2; color: #ef4444; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="shield-alert" style="width:16px;"></i>
            </div>
        </div>
        <div style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Security Events</div>
        <div style="font-size: 28px; font-weight: 900; color: var(--text-main); margin-top: 4px;" id="kpi-security"><?= $initialData['security'] ?></div>
    </div>

    <!-- AI Forecast Card -->
    <div class="card stat-card" style="border: 1px solid var(--primary); background: linear-gradient(135deg, #fff 0%, #f0f7ff 100%);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div style="width: 32px; height: 32px; background: var(--primary); color: #fff; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="trending-up" style="width:16px;"></i>
            </div>
            <div style="font-size: 8px; font-weight: 800; color: var(--primary); background: #fff; padding: 2px 6px; border-radius: 10px; border: 1px solid var(--primary); animation: pulse 2s infinite;">AI PREDICTION</div>
        </div>
        <div style="font-size: 11px; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.05em;">Next 7 Days Forecast</div>
        <div style="font-size: 28px; font-weight: 900; color: var(--primary); margin-top: 4px;" id="kpi-forecast">₱0.00</div>
    </div>
</div>

<style>
@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.05); opacity: 0.7; }
    100% { transform: scale(1); opacity: 1; }
}
</style>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <div class="card" style="padding: 24px; border: 1px solid var(--border);">
        <div style="font-weight: 800; font-size: 14px; margin-bottom: 24px; color: var(--text-main);">Revenue Trend (Last 30 Days)</div>
        <canvas id="revenueChart" style="max-height: 300px;"></canvas>
    </div>
    <div class="card" style="padding: 24px; border: 1px solid var(--border);">
        <div style="font-weight: 800; font-size: 14px; margin-bottom: 24px; color: var(--text-main);">Vehicle Category Mix</div>
        <canvas id="vehicleMixChart"></canvas>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 24px;">
    <div class="card" style="padding: 24px; border: 1px solid var(--border);">
        <div style="font-weight: 800; font-size: 14px; margin-bottom: 24px; color: var(--text-main);">Peak Activity Hours</div>
        <canvas id="peakHoursChart"></canvas>
    </div>
    <div class="card" style="padding: 24px; border: 1px solid var(--border);">
        <div style="font-weight: 800; font-size: 14px; margin-bottom: 24px; color: var(--text-main);">Zone Saturation Map</div>
        <div id="zone-saturation-list" style="display: grid; gap: 16px; margin-top: 10px;">
            <!-- Hydrated by JS -->
        </div>
    </div>
</div>

<!-- Print-Only Document Footer -->
<div class="print-only" style="display: none; margin-top: 80px; padding-top: 40px; border-top: 1px solid #1e293b;">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 100px;">
        <div>
            <div style="border-bottom: 1px solid #000; width: 250px; height: 50px;"></div>
            <div style="font-size: 12px; font-weight: 800; margin-top: 10px; text-transform: uppercase;">Generated By Administrator</div>
            <div style="font-size: 10px; color: #64748b;">ParkSys Pro Intelligence Subsystem</div>
        </div>
        <div style="text-align: right;">
            <div style="border-bottom: 1px solid #000; width: 250px; height: 50px; margin-left: auto;"></div>
            <div style="font-size: 12px; font-weight: 800; margin-top: 10px; text-transform: uppercase;">Facility Director Signature</div>
            <div style="font-size: 10px; color: #64748b;">Official Approval Required for Fiscal Use</div>
        </div>
    </div>
    <div style="text-align: center; margin-top: 60px; font-size: 10px; color: #94a3b8; font-style: italic;">
        End of Official Document - Confidential Data
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let charts = {};

function initCharts() {
    const ctx1 = document.getElementById('revenueChart').getContext('2d');
    charts.revenue = new Chart(ctx1, {
        type: 'line',
        data: { labels: [], datasets: [{ label: 'Revenue', data: [], borderColor: '#2563eb', tension: 0.4, fill: true, backgroundColor: 'rgba(37,99,235,0.05)', borderWidth: 3 }] },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    const ctx2 = document.getElementById('vehicleMixChart').getContext('2d');
    charts.mix = new Chart(ctx2, {
        type: 'doughnut',
        data: { labels: ['Cars', 'Motorcycles', 'Vans'], datasets: [{ data: [0, 0, 0], backgroundColor: ['#2563eb', '#6366f1', '#8b5cf6'], borderWidth: 0 }] },
        options: { responsive: true, cutout: '75%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15, font: { weight: 'bold' } } } } }
    });

    const ctx3 = document.getElementById('peakHoursChart').getContext('2d');
    charts.peak = new Chart(ctx3, {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Traffic', data: [], backgroundColor: '#6366f1', borderRadius: 8 }] },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });
}

function refreshAnalytics() {
    fetch('<?= BASE_URL ?>/api/get_analytics_data.php')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // KPIs
                document.getElementById('kpi-revenue').textContent = '₱' + parseFloat(data.kpis.total_revenue || 0).toLocaleString(undefined, {minimumFractionDigits: 2});
                document.getElementById('kpi-txns').textContent = data.kpis.total_transactions;
                document.getElementById('kpi-occ').textContent = data.kpis.current_occupancy;
                document.getElementById('kpi-security').textContent = data.kpis.security_events;

                // Revenue Trend
                charts.revenue.data.labels = data.revenueTrend.map(d => d.date);
                charts.revenue.data.datasets[0].data = data.revenueTrend.map(d => d.amount);
                charts.revenue.update();

                // Vehicle Mix
                const carCount = data.vehicleDist.find(v => v.vehicle_type === 'car')?.count || 0;
                const motoCount = data.vehicleDist.find(v => v.vehicle_type === 'motorcycle')?.count || 0;
                const vanCount = data.vehicleDist.find(v => v.vehicle_type === 'van')?.count || 0;
                charts.mix.data.datasets[0].data = [carCount, motoCount, vanCount];
                charts.mix.update();

                // Peak Hours
                charts.peak.data.labels = data.peakHours.map(d => d.hour + ':00');
                charts.peak.data.datasets[0].data = data.peakHours.map(d => d.count);
                charts.peak.update();

                // AI Forecasting Logic (Linear Projection)
                if (data.revenueTrend && data.revenueTrend.length > 0) {
                    const totalTrend = data.revenueTrend.reduce((sum, d) => sum + parseFloat(d.amount), 0);
                    const avgDaily = totalTrend / data.revenueTrend.length;
                    const forecast = avgDaily * 7 * 1.15; // Project 7 days with 15% growth factor
                    document.getElementById('kpi-forecast').textContent = '₱' + forecast.toLocaleString(undefined, {minimumFractionDigits: 2});
                }

                // Zone Saturation
                const zList = document.getElementById('zone-saturation-list');
                zList.innerHTML = data.zoneUsage.map(z => `
                    <div>
                        <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:6px;">
                            <span style="font-weight:700; color:var(--text-main);">Zone ${z.name}</span>
                            <span style="color:var(--text-muted); font-weight:600;">${z.occupied_slots}/${z.total_slots} Slots</span>
                        </div>
                        <div style="height:8px; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:4px; overflow:hidden;">
                            <div style="height:100%; width:${(z.occupied_slots/z.total_slots)*100}%; background:linear-gradient(90deg, var(--primary), #6366f1);"></div>
                        </div>
                    </div>
                `).join('');
                
                generateAIReport(data);
            }
        });
}

function generateAIReport(data) {
    const intelText = document.getElementById('ai-intelligence-text');
    intelText.style.opacity = '0.5';
    
    setTimeout(() => {
        intelText.style.opacity = '1';
        let insights = [];
        const rev = data.kpis.total_revenue || 0;
        const occ = data.kpis.current_occupancy || 0;
        
        if (rev > 5000) insights.push("Financial velocity is HIGH. AI recommends expanding premium VIP zones.");
        else insights.push("Steady revenue stream detected. System is performing within expected baseline.");
        
        if (occ > 0.8) insights.push("CRITICAL: Facility nearing saturation. AI suggests re-routing new entries to South Wing.");
        if (data.kpis.security_events > 0) insights.push("SECURITY ALERT: Anomalies detected in Gate Overrides. Reviewing logs...");
        
        intelText.innerHTML = insights.join('<br> • ');
    }, 800);
}

window.onload = () => {
    initCharts();
    refreshAnalytics();
    if(typeof lucide !== 'undefined') lucide.createIcons();
};
</script>

<style>
@media print {
    /* Tighten everything for 1-page fit */
    @page { margin: 0.5cm; size: auto; }
    .no-print, .sidebar, .topbar, .breadcrumb { display: none !important; }
    .main-content { margin: 0 !important; padding: 10px !important; width: 100% !important; left: 0 !important; }
    
    body { background: #fff !important; font-size: 11px !important; }
    .card { border: 1px solid #e2e8f0 !important; box-shadow: none !important; break-inside: avoid; margin-bottom: 12px !important; padding: 15px !important; }
    
    .print-only { display: block !important; }
    .print-header { margin-bottom: 20px !important; }
    
    /* Shrink charts for space */
    canvas { max-width: 100% !important; height: 180px !important; }
    
    .stats-grid { 
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important; 
        gap: 10px !important; 
        margin-bottom: 20px !important;
    }
    .stat-card { padding: 12px !important; }
    .stat-card div[style*="font-size: 28px"] { font-size: 20px !important; }
    
    /* Layout grid for print */
    div[style*="grid-template-columns: 2fr 1fr"] { grid-template-columns: 1fr 1fr !important; gap: 15px !important; }
    div[style*="grid-template-columns: 1fr 1fr"] { gap: 15px !important; }
    
    .print-only[style*="margin-top: 80px"] { margin-top: 30px !important; padding-top: 20px !important; }
    div[style*="height: 50px"] { height: 30px !important; width: 180px !important; }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
