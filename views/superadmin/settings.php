<?php
// views/superadmin/settings.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_SUPERADMIN);

$db = Database::getConnection();

// Handle Form Submission
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    foreach ($_POST as $key => $value) {
        if ($key === 'save_settings') continue;
        $stmt = $db->prepare("INSERT INTO system_settings (meta_key, meta_value) VALUES (:key, :val) ON DUPLICATE KEY UPDATE meta_value = :val2");
        $stmt->execute([':key' => $key, ':val' => $value, ':val2' => $value]);
    }
    $message = "Settings updated successfully!";
}

// Fetch all settings
$app_name      = getSetting($db, 'app_name', 'ParkSys Pro');
$app_address   = getSetting($db, 'app_address', '');
$currency      = getSetting($db, 'currency', 'PHP');
$grace_period  = getSetting($db, 'grace_period', '15');
$tax_rate      = getSetting($db, 'tax_rate', '12');
$receipt_footer = getSetting($db, 'receipt_footer', '');

$pageTitle = 'System Settings';
ob_start();
?>

<div style="max-width: 1100px;">
    <div style="margin-bottom: 32px;">
        <h2 class="section-title">Global Configuration</h2>
        <p style="font-size: 13px; color: var(--text-muted);">Manage system-wide parameters, branding, and operational rules.</p>
    </div>

    <?php if ($message): ?>
        <div style="padding: 14px 20px; background: var(--success); color: #fff; border-radius: 8px; margin-bottom: 24px; font-size: 14px; animation: slideIn 0.3s ease-out;">
            <i data-lucide="check-circle" style="width:16px; margin-right:8px; vertical-align:middle;"></i> <?= $message ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['security_error'])): ?>
        <div style="padding: 14px 20px; background: #ef4444; color: #fff; border-radius: 8px; margin-bottom: 24px; font-size: 14px; font-weight: 600; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2); animation: shake 0.5s;">
            <i data-lucide="shield-alert" style="width:16px; margin-right:8px; vertical-align:middle;"></i> Security Violation: Invalid Superadmin Password. Access to Architectural Export was denied.
        </div>
    <?php endif; ?>

<style>
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}
@keyframes slideIn {
    from { transform: translateY(-10px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
</style>

    <div class="settings-grid">
        <!-- Nav Tabs -->
        <div style="display: flex; flex-direction: column; gap: 6px;" id="settings-tabs">
            <button class="nav-item active" style="width:100%; text-align:left; border:none; background:transparent; cursor:pointer;" onclick="switchTab('branding', this)">
                <i data-lucide="layout" style="width:16px;"></i> General Branding
            </button>
            <button class="nav-item" style="width:100%; text-align:left; border:none; background:transparent; cursor:pointer;" onclick="switchTab('billing', this)">
                <i data-lucide="receipt" style="width:16px;"></i> Receipt & Billing
            </button>
            <button class="nav-item" style="width:100%; text-align:left; border:none; background:transparent; cursor:pointer;" onclick="switchTab('security', this)">
                <i data-lucide="shield" style="width:16px;"></i> Security & AI
            </button>
            <button class="nav-item" style="width:100%; text-align:left; border:none; background:transparent; cursor:pointer;" onclick="switchTab('database', this)">
                <i data-lucide="database" style="width:16px;"></i> Database Tools
            </button>
        </div>

        <!-- Content Area -->
        <form method="POST">
            <div class="card settings-section" id="section-branding">
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 32px;">General Branding</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                    <div class="form-group">
                        <label class="label">Organization Name</label>
                        <input type="text" name="app_name" class="input" value="<?= htmlspecialchars($app_name) ?>">
                    </div>
                    <div class="form-group">
                        <label class="label">System Version</label>
                        <input type="text" class="input" value="<?= APP_VERSION ?>" disabled>
                    </div>
                </div>
                <div class="form-group">
                    <label class="label">Facility Address</label>
                    <textarea name="app_address" class="input" style="height: 100px;"><?= htmlspecialchars($app_address) ?></textarea>
                </div>
            </div>

            <div class="card settings-section" id="section-billing" style="display: none;">
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 32px;">Receipt & Billing</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                    <div class="form-group">
                        <label class="label">Grace Period (Minutes)</label>
                        <input type="number" name="grace_period" class="input" value="<?= $grace_period ?>">
                    </div>
                    <div class="form-group">
                        <label class="label">VAT Rate (%)</label>
                        <input type="number" name="tax_rate" class="input" value="<?= $tax_rate ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="label">Receipt Footer Message</label>
                    <input type="text" name="receipt_footer" class="input" value="<?= htmlspecialchars($receipt_footer) ?>">
                </div>
            </div>

            <div class="card settings-section" id="section-security" style="display: none;">
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 32px;">Security & AI</h3>
                <div class="form-group">
                    <label class="label">AI Plate Recognition</label>
                    <select name="ai_scanning" class="select">
                        <option value="enabled" <?= getSetting($db, 'ai_scanning') === 'enabled' ? 'selected' : '' ?>>Enabled (Standard)</option>
                        <option value="disabled" <?= getSetting($db, 'ai_scanning') === 'disabled' ? 'selected' : '' ?>>Disabled</option>
                    </select>
                </div>
                <div style="padding: 16px; background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; color: #92400e; font-size: 13px;">
                    <strong>Security Note:</strong> Changes here affect the automated entry simulation modules.
                </div>
            </div>

            <div class="card settings-section" id="section-database" style="display: none;">
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 32px;">Database Tools</h3>
                <div style="display: grid; gap: 16px;">
                    <button type="button" onclick="openSecurityModal()" class="btn btn-secondary" style="justify-content: flex-start; gap: 12px; width: 100%; padding: 20px;">
                        <i data-lucide="download"></i> 
                        <div style="text-align: left;">
                            <div style="font-weight: 700;">Export Full Database</div>
                            <div style="font-size: 11px; opacity: 0.7;">Download a .sql backup of the entire system.</div>
                        </div>
                    </button>
                    <button type="button" class="btn btn-secondary" style="justify-content: flex-start; gap: 12px; width: 100%; padding: 20px; color: var(--danger); border-color: #fee2e2;">
                        <i data-lucide="trash-2"></i> 
                        <div style="text-align: left;">
                            <div style="font-weight: 700;">Clear Transaction Logs</div>
                            <div style="font-size: 11px; opacity: 0.7;">Wipe all history logs. Use with extreme caution.</div>
                        </div>
                    </button>
                </div>
            </div>

            <div style="margin-top: 32px; display: flex; justify-content: flex-end;">
                <button type="submit" name="save_settings" class="btn btn-primary" style="padding: 14px 40px;">Save All Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Security Verification Modal -->
<div class="modal-overlay" id="security-modal" onclick="closeModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="width: 56px; height: 56px; background: #fef2f2; color: var(--danger); border-radius: 14px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <i data-lucide="shield-check" style="width:28px; height:28px;"></i>
            </div>
            <h2 style="font-size: 18px; font-weight: 800;">High-Security Access</h2>
            <p style="font-size: 12px; color: var(--text-muted);">Please verify your Superadmin password to proceed with the database architectural export.</p>
        </div>
        <form id="export-verify-form" action="<?= BASE_URL ?>/api/export_db.php" method="POST">
            <div class="form-group">
                <label class="label">Admin Password</label>
                <input type="password" name="verify_password" class="input" placeholder="Enter your password" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 24px;">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary" style="background: var(--danger); border-color: var(--danger);">Verify & Download</button>
            </div>
        </form>
    </div>
</div>

<script>
function switchTab(sectionId, btn) {
    // Hide all sections
    document.querySelectorAll('.settings-section').forEach(s => s.style.display = 'none');
    // Show target
    document.getElementById('section-' + sectionId).style.display = 'block';
    
    // Update active state in tabs
    document.querySelectorAll('#settings-tabs .nav-item').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    
    lucide.createIcons();
}

function openSecurityModal() {
    document.getElementById('security-modal').classList.add('open');
}

function closeModal(e) {
    if (!e || e.target.classList.contains('modal-overlay')) {
        document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('open'));
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
