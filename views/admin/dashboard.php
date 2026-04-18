<?php
// views/admin/dashboard.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_ADMIN, ROLE_SUPERADMIN);

$db = Database::getConnection();

// Stats with real data
$stats = $db->query("
    SELECT
        SUM(status = 'available') as available,
        SUM(status = 'occupied') as occupied,
        (SELECT SUM(total_fee) FROM transactions WHERE DATE(paid_at) = CURDATE()) as revenue,
        (SELECT COUNT(*) FROM sessions WHERE status = 'active') as active_sessions
    FROM slots
")->fetch();

// Group slots by Zone
$stmt = $db->query("
    SELECT s.*, z.name as zone_name, sess.plate_number, sess.entry_time, sess.vehicle_type as v_type
    FROM slots s
    JOIN zones z ON s.zone_id = z.id
    LEFT JOIN sessions sess ON s.id = sess.slot_id AND sess.status = 'active'
    ORDER BY z.name, s.slot_code
");
$all_slots = $stmt->fetchAll();

$byZone = [];
foreach ($all_slots as $s) {
    $byZone[$s['zone_name']][] = $s;
}

// Check for "Overstaying" vehicles (> 4 hours)
$overstaying = [];
foreach ($all_slots as $s) {
    if ($s['status'] === 'occupied' && $s['entry_time']) {
        $entry = new DateTime($s['entry_time']);
        $now = new DateTime();
        $diff = $now->getTimestamp() - $entry->getTimestamp();
        if ($diff > 4 * 3600) { // 4 hours
            $overstaying[] = $s;
        }
    }
}

$pageTitle = 'Live Monitor';
ob_start();
?>

<!-- Advanced Alerts -->
<div style="display: flex; gap: 24px; margin-bottom: 32px;">
    <?php if (!empty($overstaying)): ?>
    <div style="flex: 1; background: #fff; border-left: 4px solid var(--danger); padding: 16px 24px; border-radius: 8px; display: flex; align-items: center; justify-content: space-between; box-shadow: var(--shadow-sm);">
        <div style="display: flex; align-items: center; gap: 16px;">
            <i data-lucide="alert-triangle" style="color: var(--danger);"></i>
            <div>
                <div style="font-weight: 700; color: var(--danger); font-size: 14px;">OVERSTAY ALERT</div>
                <div style="font-size: 13px; color: var(--text-muted);">
                    <?= count($overstaying) ?> vehicles have exceeded threshold.
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <button onclick="emergencyOverride()" style="padding: 0 24px; background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 10px; transition: 0.2s;">
        <i data-lucide="shield-alert" style="width:18px"></i>
        EMERGENCY GATE OVERRIDE
    </button>
</div>

<script>
function emergencyOverride() {
    if(confirm('⚠️ WARNING: You are about to FORCE OPEN all gates. This action will be logged and reported to the Superadmin. Proceed only in case of fire or emergency.')) {
        alert('GATES OPENED. System logging security event...');
        // In real world, send API request to log and trigger hardware
    }
}
</script>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Available Slots</div>
        <div class="stat-val" style="color: var(--success);"><?= $stats['available'] ?? 0 ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Current Occupancy</div>
        <div class="stat-val"><?= $stats['occupied'] ?? 0 ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Today's Revenue</div>
        <div class="stat-val"><?= peso($stats['revenue'] ?? 0) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Active Sessions</div>
        <div class="stat-val"><?= $stats['active_sessions'] ?? 0 ?></div>
    </div>
</div>

<?php foreach ($byZone as $zoneName => $slots): ?>
<div class="zone-section">
    <header class="section-header">
        <h3 class="section-title"><?= htmlspecialchars($zoneName) ?></h3>
    </header>
    <div class="slot-grid">
        <?php foreach ($slots as $s): ?>
            <div class="slot status-<?= $s['status'] ?>" 
                 onclick="<?= $s['status'] === 'available' ? "window.location.href='entry.php?slot={$s['id']}'" : "window.location.href='exit.php?plate={$s['plate_number']}'" ?>"
                 title="<?= $s['status'] === 'occupied' ? "Plate: {$s['plate_number']} | Entry: ".date('H:i', strtotime($s['entry_time'])) : 'Available' ?>">
                
                <div class="slot-status-indicator"></div>
                <div class="slot-code"><?= $s['slot_code'] ?></div>
                
                <?php if ($s['status'] === 'occupied'): ?>
                    <div style="font-size: 10px; font-weight: 700; font-family: var(--font-mono); margin-top: 4px; color: var(--primary);">
                        <?= $s['plate_number'] ?>
                    </div>
                <?php endif; ?>

                <!-- Overstay Icon -->
                <?php 
                    if ($s['status'] === 'occupied' && $s['entry_time']) {
                        $diff = time() - strtotime($s['entry_time']);
                        if ($diff > 4 * 3600) {
                            echo '<i data-lucide="clock-alert" style="position:absolute; top:4px; right:4px; width:12px; color:var(--danger)"></i>';
                        }
                    }
                ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>