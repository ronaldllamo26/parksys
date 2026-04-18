<?php
// views/admin/shift.php — Real-world Shift Handover Report
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_ADMIN, ROLE_SUPERADMIN);

$db = Database::getConnection();
$userId = $_SESSION[SESSION_USER_ID];

// Fetch shift stats (only transactions handled by this specific user TODAY)
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_processed,
        SUM(total_fee) as total_collected,
        MIN(paid_at) as shift_start,
        MAX(paid_at) as last_transaction
    FROM transactions 
    WHERE handled_by = :uid 
    AND DATE(paid_at) = CURDATE()
");
$stmt->execute([':uid' => $userId]);
$shift = $stmt->fetch();

$pageTitle = 'My Shift Report';
ob_start();
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px;">
        <div>
            <h2 class="section-title">Current Shift Summary</h2>
            <p style="font-size: 13px; color: var(--text-muted);">Review your transaction performance and prepare for handover.</p>
        </div>
        <button class="btn btn-secondary" onclick="window.print()">
            <i data-lucide="printer" style="width:16px; margin-right:8px;"></i> Print X-Read
        </button>
    </div>

    <div class="grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
        <!-- Cash Collection Card -->
        <div class="card" style="background: var(--primary); color: #fff; border: none;">
            <div style="font-size: 12px; font-weight: 600; opacity: 0.8; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em;">Total Cash on Hand</div>
            <div style="font-size: 42px; font-weight: 800; font-family: var(--font-mono);"><?= peso($shift['total_collected'] ?? 0) ?></div>
            <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.1); font-size: 13px;">
                Handled <?= $shift['total_processed'] ?? 0 ?> vehicles today.
            </div>
        </div>

        <!-- Shift Info Card -->
        <div class="card">
            <div style="display: grid; gap: 16px;">
                <div>
                    <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Shift Started</div>
                    <div style="font-weight: 700; font-size: 15px;"><?= $shift['shift_start'] ? date('h:i A', strtotime($shift['shift_start'])) : '--:--' ?></div>
                </div>
                <div>
                    <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Last Transaction</div>
                    <div style="font-weight: 700; font-size: 15px;"><?= $shift['last_transaction'] ? date('h:i A', strtotime($shift['last_transaction'])) : 'No activity' ?></div>
                </div>
                <div>
                    <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Cashier Identity</div>
                    <div style="font-weight: 700; font-size: 15px;"><?= htmlspecialchars($_SESSION[SESSION_USER_NAME]) ?> (ID: #<?= $userId ?>)</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="border-left: 4px solid var(--warning); background: var(--primary-light);">
        <div style="display: flex; gap: 16px; align-items: flex-start;">
            <i data-lucide="info" style="color: var(--primary);"></i>
            <div>
                <div style="font-weight: 700; color: var(--text-main); font-size: 14px; margin-bottom: 4px;">End of Shift Instruction</div>
                <div style="font-size: 13px; color: var(--text-muted); line-height: 1.5;">
                    Please ensure all physical cash matches the **Total Collected** amount shown above. 
                    Once confirmed, click the button below to sign out and clear your terminal for the next officer.
                </div>
                <button class="btn btn-primary" style="margin-top: 20px;" onclick="confirmHandover()">Finalize & Close Shift</button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmHandover() {
    if(confirm('Are you sure you want to close your shift? This will record your final remittance.')) {
        window.location.href = '<?= BASE_URL ?>/api/auth_logout.php';
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
