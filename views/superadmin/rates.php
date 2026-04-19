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

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
    <div>
        <h2 class="section-title" style="margin-bottom: 4px;">Revenue & Pricing Strategy</h2>
        <p style="font-size: 13px; color: var(--text-muted);">Manage parking fees and grace periods for all vehicle categories.</p>
    </div>
    <button class="btn btn-primary" onclick="openRateModal()">
        <i data-lucide="edit-3" style="width:14px; margin-right:8px; vertical-align:middle;"></i> Update Pricing Rates
    </button>
</div>

<div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 24px;">
    <?php foreach ($rates as $r): 
        $isVipRate = ($r['vehicle_type'] === 'vip');
    ?>
    <div class="card" style="padding: 32px; position: relative; border: 1px solid <?= $isVipRate ? 'var(--primary)' : 'var(--border)' ?>; overflow: hidden; <?= $isVipRate ? 'background: linear-gradient(135deg, #fff 0%, #f0f7ff 100%);' : '' ?>">
        <?php if ($isVipRate): ?>
            <div style="position: absolute; top: 12px; right: 12px; font-size: 8px; font-weight: 800; color: var(--primary); background: #fff; padding: 4px 10px; border-radius: 10px; border: 1px solid var(--primary); box-shadow: 0 2px 4px rgba(37,99,235,0.1);">SYSTEM LOCKED</div>
        <?php endif; ?>

        <!-- Background Icon Accent -->
        <div style="position: absolute; top: -20px; right: -20px; opacity: 0.05; transform: rotate(-15deg);">
            <i data-lucide="<?= $r['vehicle_type'] === 'car' ? 'car' : ($r['vehicle_type'] === 'motorcycle' ? 'bike' : ($r['vehicle_type'] === 'vip' ? 'award' : 'truck')) ?>" style="width: 140px; height: 140px;"></i>
        </div>

        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
            <div style="width: 40px; height: 40px; background: <?= $isVipRate ? 'var(--primary)' : 'var(--primary-light)' ?>; color: <?= $isVipRate ? '#fff' : 'var(--primary)' ?>; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="<?= $r['vehicle_type'] === 'car' ? 'car' : ($r['vehicle_type'] === 'motorcycle' ? 'bike' : ($r['vehicle_type'] === 'vip' ? 'award' : 'truck')) ?>" style="width: 20px;"></i>
            </div>
            <div>
                <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--primary); letter-spacing: 0.1em;">
                    <?= htmlspecialchars($r['vehicle_type']) ?> Category
                </div>
                <div style="font-size: 13px; color: var(--text-muted); font-weight: 500;"><?= $isVipRate ? 'System Constant' : 'Active Strategy' ?></div>
            </div>
        </div>

        <div style="margin-bottom: 24px;">
            <div style="font-size: 48px; font-weight: 900; color: var(--text-main); line-height: 1;">
                <?= peso($r['first_hour_fee']) ?>
                <span style="font-size: 14px; font-weight: 600; color: var(--text-muted); letter-spacing: 0;">/ 1st hour</span>
            </div>
        </div>
        
        <div style="background: <?= $isVipRate ? '#fff' : 'var(--bg)' ?>; border: 1px solid var(--border); border-radius: 16px; padding: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">Excess / Hr</div>
                <div style="font-weight: 800; color: var(--text-main); font-size: 15px;"><?= peso($r['excess_hour_fee']) ?></div>
            </div>
            <div>
                <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">Grace Period</div>
                <div style="font-weight: 800; color: var(--text-main); font-size: 15px;"><?= $r['grace_minutes'] ?> mins</div>
            </div>
            <div style="grid-column: span 2; border-top: 1px dashed var(--border); padding-top: 12px; margin-top: 4px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">24-Hr Flat Cap</div>
                    <div style="font-weight: 800; color: var(--primary);"><?= $r['flat_day_rate'] ? peso($r['flat_day_rate']) : 'No Limit' ?></div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status</div>
                    <div style="font-weight: 600; font-size: 12px; color: <?= $isVipRate ? 'var(--primary)' : 'var(--success)' ?>;"><?= $isVipRate ? 'PROTECTED' : 'LIVE' ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Update Rate Modal -->
<div class="modal-overlay" id="rate-modal" onclick="closeModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div style="text-align: center; margin-bottom: 32px;">
            <div style="width: 56px; height: 56px; background: var(--primary-light); color: var(--primary); border-radius: 14px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <i data-lucide="trending-up" style="width:28px; height:28px;"></i>
            </div>
            <h2 style="font-size: 20px; font-weight: 800;">Update Pricing Strategy</h2>
            <p style="font-size: 13px; color: var(--text-muted);">Configure new rates that will take effect immediately.</p>
        </div>
        <form id="update-rate-form">
            <div class="form-group">
                <label class="label">Vehicle Category</label>
                <select class="select" style="width: 100%;">
                    <option value="car">Standard Car</option>
                    <option value="motorcycle">Motorcycle</option>
                    <option value="van">Van / SUV</option>
                </select>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="label">First Hour Fee (₱)</label>
                    <input type="number" class="input" value="40" step="1">
                </div>
                <div class="form-group">
                    <label class="label">Excess Hour Fee (₱)</label>
                    <input type="number" class="input" value="10" step="1">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="label">Grace Period (Mins)</label>
                    <input type="number" class="input" value="15">
                </div>
                <div class="form-group">
                    <label class="label">24-Hr Max Cap (₱)</label>
                    <input type="number" class="input" placeholder="e.g. 300">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 24px;">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Publish New Rates</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRateModal() { document.getElementById('rate-modal').classList.add('open'); }
function closeModal(e) {
    if (!e || e.target.classList.contains('modal-overlay')) {
        document.getElementById('rate-modal').classList.remove('open');
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
