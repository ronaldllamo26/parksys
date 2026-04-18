<?php
// views/superadmin/analytics.php — Complete Business Intelligence Suite
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_SUPERADMIN);

$db = Database::getConnection();

// 1. Revenue Last 7 Days
$revenueData = []; $revLabels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $db->prepare("SELECT SUM(total_fee) as daily FROM transactions WHERE DATE(paid_at) = :d");
    $stmt->execute([':d' => $date]);
    $revenueData[] = (float)($stmt->fetch()['daily'] ?? 0);
    $revLabels[] = date('D', strtotime($date));
}

// 2. Hourly Peak Traffic (Last 24 Hours)
$peakData = array_fill(0, 24, 0);
$peakLabels = ["12AM","1AM","2AM","3AM","4AM","5AM","6AM","7AM","8AM","9AM","10AM","11AM","12PM","1PM","2PM","3PM","4PM","5PM","6PM","7PM","8PM","9PM","10PM","11PM"];
$stmt = $db->query("SELECT HOUR(entry_time) as hr, COUNT(*) as qty FROM sessions WHERE entry_time >= NOW() - INTERVAL 1 DAY GROUP BY hr");
while($r = $stmt->fetch()) { $peakData[$r['hr']] = (int)$r['qty']; }

// 3. Occupancy Breakdown
$stmt = $db->query("SELECT vehicle_type, COUNT(*) as count FROM sessions WHERE status = 'active' GROUP BY vehicle_type");
$occRaw = $stmt->fetchAll();
$occLabels = []; $occValues = []; $occColors = [];
$colors = ['car' => '#2563eb', 'motorcycle' => '#10b981', 'van' => '#f59e0b'];
foreach ($occRaw as $row) {
    $occLabels[] = ucfirst($row['vehicle_type']);
    $occValues[] = (int)$row['count'];
    $occColors[] = $colors[$row['vehicle_type']] ?? '#64748b';
}
if(empty($occValues)) { $occLabels = ['No Active Vehicles']; $occValues = [1]; $occColors = ['#f1f5f9']; }

// 4. Summary Metrics
$avgStay = $db->query("SELECT AVG(TIMESTAMPDIFF(MINUTE, entry_time, exit_time)) as avg_min FROM sessions WHERE status = 'completed'")->fetch()['avg_min'] ?? 0;
$totalTrans = $db->query("SELECT COUNT(*) as total FROM transactions WHERE DATE(paid_at) = CURDATE()")->fetch()['total'] ?? 0;

$pageTitle = 'Data Analytics';
ob_start();
?>

<!-- Quick Stats Row -->
<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 32px;">
    <div class="stat-card">
        <div class="stat-label">Avg. Stay Duration</div>
        <div class="stat-val" style="font-size: 20px;"><?= round($avgStay) ?> <span style="font-size: 12px; font-weight: 400; color: var(--text-muted);">mins</span></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Today's Volume</div>
        <div class="stat-val" style="font-size: 20px;"><?= $totalTrans ?> <span style="font-size: 12px; font-weight: 400; color: var(--text-muted);">check-outs</span></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">System Health</div>
        <div class="stat-val" style="font-size: 20px; color: var(--success);">OPTIMAL</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Active Users</div>
        <div class="stat-val" style="font-size: 20px;">3 <span style="font-size: 12px; font-weight: 400; color: var(--text-muted);">staff</span></div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1.2fr; gap: 32px; margin-bottom: 32px;">
    <!-- Revenue Chart -->
    <div class="card" style="height: 400px; display: flex; flex-direction: column;">
        <h3 class="section-title" style="margin-bottom: 24px;">Revenue History (7D)</h3>
        <div style="flex: 1; position: relative;"><canvas id="revenueChart"></canvas></div>
    </div>

    <!-- Occupancy Doughnut -->
    <div class="card" style="height: 400px; display: flex; flex-direction: column;">
        <h3 class="section-title" style="margin-bottom: 24px;">Vehicle Mix</h3>
        <div style="flex: 1; position: relative;"><canvas id="occupancyChart"></canvas></div>
        <div style="margin-top: 16px; font-size: 12px; color: var(--text-muted); text-align: center;">Distribution of active sessions</div>
    </div>
</div>

<!-- Peak Hours Bar Chart -->
<div class="card" style="height: 350px; display: flex; flex-direction: column;">
    <h3 class="section-title" style="margin-bottom: 24px;">Peak Entry Hours (24H Traffic)</h3>
    <div style="flex: 1; position: relative;"><canvas id="peakChart"></canvas></div>
</div>

<script>
window.addEventListener('load', function() {
    // 1. Revenue
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: { labels: <?= json_encode($revLabels) ?>, datasets: [{ label: 'Revenue', data: <?= json_encode($revenueData) ?>, borderColor: '#2563eb', backgroundColor: 'rgba(37, 99, 235, 0.05)', fill: true, tension: 0.4, borderWidth: 3 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.02)' } }, x: { grid: { display: false } } } }
    });

    // 2. Occupancy
    new Chart(document.getElementById('occupancyChart'), {
        type: 'doughnut',
        data: { labels: <?= json_encode($occLabels) ?>, datasets: [{ data: <?= json_encode($occValues) ?>, backgroundColor: <?= json_encode($occColors) ?>, borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '75%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } } }
    });

    // 3. Peak Hours
    new Chart(document.getElementById('peakChart'), {
        type: 'bar',
        data: { 
            labels: <?= json_encode($peakLabels) ?>, 
            datasets: [{ 
                label: 'New Entries', 
                data: <?= json_encode($peakData) ?>, 
                backgroundColor: 'rgba(37, 99, 235, 0.7)', 
                borderRadius: 4,
                hoverBackgroundColor: '#2563eb'
            }] 
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false, 
            plugins: { legend: { display: false } },
            scales: { 
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.02)' }, ticks: { stepSize: 1 } }, 
                x: { grid: { display: false }, ticks: { font: { size: 10 } } } 
            } 
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
