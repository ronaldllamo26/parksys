<?php
// views/admin/dashboard.php — Advanced Staff Ops Center
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_ADMIN, ROLE_SUPERADMIN);

$db = Database::getConnection();
$uid = $_SESSION[SESSION_USER_ID];

// Fetch Rates for Reference
$rates = $db->query("SELECT * FROM rates WHERE is_current = 1 ORDER BY vehicle_type ASC")->fetchAll();

// Fetch Grid Data grouped by Floor (with logical sorting)
$stmt = $db->query("
    SELECT s.*, z.name as zone_name, z.floor as zone_floor, sess.plate_number, sess.entry_time, sess.vehicle_type as v_type
    FROM slots s
    JOIN zones z ON s.zone_id = z.id
    LEFT JOIN sessions sess ON s.id = sess.slot_id AND sess.status = 'active'
    ORDER BY z.floor, z.name, s.slot_code
");
$all_slots = $stmt->fetchAll();

$floors = [];
foreach ($all_slots as $s) {
    $f = $s['zone_floor'] ?: 'Ground Floor';
    $floors[$f][$s['zone_name']][] = $s;
}

// Custom Sort: Prioritize Ground Floor
uksort($floors, function($a, $b) {
    if (stripos($a, 'Ground') !== false || stripos($a, 'G/F') !== false) return -1;
    if (stripos($b, 'Ground') !== false || stripos($b, 'G/F') !== false) return 1;
    return strnatcasecmp($a, $b);
});

$pageTitle = 'Live Monitor';
ob_start();
?>

<div style="display: grid; grid-template-columns: 1fr 320px; gap: 24px;">
    
    <!-- LEFT: Tactical Grid -->
    <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div>
                <h2 class="section-title" style="margin-bottom: 8px;">Facility Live Monitor</h2>
                <div style="display: flex; gap: 8px;">
                    <?php $first = true; foreach(array_keys($floors) as $fName): ?>
                        <button class="btn <?= $first ? 'btn-primary' : 'btn-secondary' ?> floor-tab-btn" 
                                onclick="switchFloor('<?= htmlspecialchars($fName) ?>', this)"
                                style="padding: 10px 20px; font-size: 11px; font-weight: 800; border-radius: 8px;">
                            <i data-lucide="layers" style="width:12px; margin-right:6px; vertical-align:middle;"></i>
                            <?= htmlspecialchars($fName) ?>
                        </button>
                    <?php $first = false; endforeach; ?>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 10px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px;">Global Vacancy</div>
                <div style="font-size: 32px; font-weight: 900; color: var(--success);" id="dash-avail">0</div>
            </div>
        </div>

        <div id="grid-root">
            <?php $first = true; foreach ($floors as $fName => $fZones): ?>
            <div class="floor-section" id="floor-<?= htmlspecialchars($fName) ?>" style="display: <?= $first ? 'block' : 'none' ?>;">
                <?php foreach ($fZones as $zoneName => $slots): ?>
                <div style="margin-bottom: 40px;">
                    <h3 style="font-size: 14px; font-weight: 800; color: var(--text-muted); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <i data-lucide="map-pin" style="width:14px; color: var(--primary);"></i>
                        <?= htmlspecialchars($zoneName) ?>
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 16px;">
                        <?php foreach ($slots as $s): ?>
                        <div class="card slot-card <?= $s['status'] ?>" 
                             onclick="<?= $s['status'] === 'available' ? "window.location.href='entry.php?slot={$s['id']}'" : "window.location.href='exit.php?plate={$s['plate_number']}'" ?>"
                             style="padding: 20px 16px; min-height: 110px; cursor: pointer; border: 1px solid var(--border); border-radius: 12px; display: flex; flex-direction: column; justify-content: space-between; position: relative; overflow: hidden; background: <?= $s['status'] === 'available' ? '#fff' : '#f8fafc' ?>;">
                            
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div style="font-size: 10px; font-weight: 900; color: var(--text-muted);"><?= $s['slot_code'] ?></div>
                                <?php if($s['slot_type'] === 'handicap'): ?>
                                    <i data-lucide="accessibility" style="width:12px; color: var(--primary);"></i>
                                <?php elseif($s['slot_type'] === 'motorcycle'): ?>
                                    <i data-lucide="bike" style="width:12px; color: #8b5cf6;"></i>
                                <?php elseif($s['slot_type'] === 'vip'): ?>
                                    <i data-lucide="star" style="width:12px; color: #f59e0b;"></i>
                                <?php endif; ?>
                            </div>
                            
                            <?php if($s['status'] === 'occupied'): ?>
                                <div>
                                    <div style="font-size: 15px; font-weight: 900; color: var(--text-main); font-family: var(--font-mono); margin-bottom: 4px;"><?= $s['plate_number'] ?></div>
                                    <div style="font-size: 9px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Stay: <span class="stay-timer" data-start="<?= $s['entry_time'] ?>">--:--</span></div>
                                </div>
                            <?php else: ?>
                                <div style="text-align: center; opacity: 0.1;">
                                    <i data-lucide="parking-circle" style="width:24px; height:24px;"></i>
                                </div>
                            <?php endif; ?>

                            <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 3px; background: <?= $s['status'] === 'available' ? 'var(--success)' : 'var(--primary)' ?>; opacity: 0.6;"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php $first = false; endforeach; ?>
        </div>
    </div>

    <!-- RIGHT: Ops Side Panel -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        
        <!-- Live AI Feed -->
        <div class="card" style="padding: 24px; background: #0f172a; border: 1px solid #1e293b; color: #fff;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px; color: #3b82f6;">
                <i data-lucide="brain" style="width:16px;"></i>
                <h4 style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em;">Tactical Insights</h4>
            </div>
            <div id="ai-status" style="font-size: 12px; line-height: 1.6; color: #94a3b8; font-weight: 500;">
                Monitoring facility flow...
            </div>
        </div>

        <!-- Quick Rates Card -->
        <div class="card" style="padding: 24px;">
            <h4 style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 16px; letter-spacing: 0.1em;">Standard Rates</h4>
            <div style="display: grid; gap: 12px;">
                <?php foreach($rates as $r): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; padding-bottom: 8px; border-bottom: 1px dashed var(--border);">
                    <div style="font-weight: 700; color: var(--text-main);"><?= $r['vehicle_type'] === 'vip' ? 'VIP Premium' : ucfirst($r['vehicle_type']) ?></div>
                    <div style="font-weight: 800; color: var(--primary);">₱<?= number_format($r['first_hour_fee'], 0) ?> <span style="font-size: 9px; color: var(--text-muted); font-weight: 400;">(1h)</span></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card" style="padding: 24px; flex: 1;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                <h4 style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted);">Activity Feed</h4>
                <div class="live-dot" id="sync-dot"></div>
            </div>
            <div id="activity-feed" style="display: flex; flex-direction: column; gap: 16px;"></div>
        </div>
    </div>
</div>

<style>
    .slot-card:hover { transform: translateY(-3px); border-color: var(--primary); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
    .slot-card.available:hover { border-color: var(--success); }
    .stay-timer { color: var(--primary); font-weight: 800; }
</style>

<script>
function switchFloor(fName, btn) {
    document.querySelectorAll('.floor-section').forEach(s => s.style.display = 'none');
    document.getElementById('floor-' + fName).style.display = 'block';
    document.querySelectorAll('.floor-tab-btn').forEach(b => {
        b.classList.remove('btn-primary'); b.classList.add('btn-secondary');
    });
    btn.classList.add('btn-primary'); btn.classList.remove('btn-secondary');
    lucide.createIcons();
}

function updateTimers() {
    document.querySelectorAll('.stay-timer').forEach(el => {
        const start = new Date(el.dataset.start);
        const now = new Date();
        const diffMs = now - start;
        const diffHrs = Math.floor(diffMs / 3600000);
        const diffMins = Math.floor((diffMs % 3600000) / 60000);
        el.textContent = `${diffHrs}h ${diffMins}m`;
    });
}

function updateDashboard() {
    fetch('<?= BASE_URL ?>/api/get_dashboard_state.php')
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            document.getElementById('dash-avail').textContent = res.stats.available;
            
            const aiStatus = document.getElementById('ai-status');
            aiStatus.innerHTML = res.ai_insights.map(i => `<div style="margin-bottom:8px; border-left:2px solid #3b82f6; padding-left:10px;">${i}</div>`).join('') || 'Optimal flow detected.';

            const feed = document.getElementById('activity-feed');
            feed.innerHTML = res.activity.map(a => `
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; padding-bottom: 8px; border-bottom: 1px solid var(--border);">
                    <span style="font-weight: 900; color: ${a.type === 'ENTRY' ? 'var(--success)' : 'var(--danger)'};">${a.type}</span>
                    <span style="font-weight: 800;">${a.plate}</span>
                    <span style="color: var(--text-muted); font-size: 10px;">${a.time}</span>
                </div>
            `).join('');

            updateTimers();
            lucide.createIcons();
        });
}

setInterval(updateDashboard, 5000);
setInterval(updateTimers, 60000); // Update timers every minute
updateDashboard();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>