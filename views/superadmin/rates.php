<?php
// views/superadmin/rates.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_SUPERADMIN);

$db = Database::getConnection();

$rates = $db->query("SELECT * FROM rates WHERE is_current = 1 ORDER BY vehicle_type ASC")->fetchAll();

$pageTitle = 'Rate Configuration';
ob_start();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h2 class="section-title">Active Pricing Rates</h2>
    <button class="btn btn-primary" onclick="alert('Updating rates feature coming soon!')">Update Rates</button>
</div>

<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
    <?php foreach ($rates as $r): ?>
    <div class="card" style="padding: 24px; position: relative;">
        <div style="position: absolute; top: 20px; right: 20px; color: var(--primary);">
            <i data-lucide="<?= $r['vehicle_type'] === 'car' ? 'car' : ($r['vehicle_type'] === 'motorcycle' ? 'bike' : 'truck') ?>"></i>
        </div>
        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--muted); letter-spacing: 0.1em; margin-bottom: 8px;">
            <?= htmlspecialchars($r['vehicle_type']) ?> Category
        </div>
        <div style="font-size: 32px; font-weight: 800; color: var(--text-main); margin-bottom: 20px;">
            <?= peso($r['first_hour_fee']) ?> <span style="font-size: 14px; font-weight: 500; color: var(--muted);">/ 1st hr</span>
        </div>
        
        <div style="border-top: 1px solid var(--border); padding-top: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div>
                <div style="font-size: 11px; color: var(--muted);">Excess / Hour</div>
                <div style="font-weight: 600;"><?= peso($r['excess_hour_fee']) ?></div>
            </div>
            <div>
                <div style="font-size: 11px; color: var(--muted);">Grace Period</div>
                <div style="font-weight: 600;"><?= $r['grace_minutes'] ?> mins</div>
            </div>
            <div>
                <div style="font-size: 11px; color: var(--muted);">Flat Day Cap</div>
                <div style="font-weight: 600;"><?= $r['flat_day_rate'] ? peso($r['flat_day_rate']) : 'None' ?></div>
            </div>
            <div>
                <div style="font-size: 11px; color: var(--muted);">Effective Date</div>
                <div style="font-weight: 600; font-size: 12px;"><?= date('M d, Y', strtotime($r['effective_from'])) ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
