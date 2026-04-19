<?php
// views/superadmin/audit.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_SUPERADMIN);

$db = Database::getConnection();

// Filtering logic
$filterUser   = isset($_GET['user_id']) ? $_GET['user_id'] : '';
$filterAction = isset($_GET['action']) ? $_GET['action'] : '';

$query = "SELECT a.*, u.name as user_name, u.role as user_role 
          FROM audit_logs a 
          LEFT JOIN users u ON a.user_id = u.id 
          WHERE 1=1";
$params = [];

if ($filterUser) {
    $query .= " AND a.user_id = :uid";
    $params[':uid'] = $filterUser;
}
if ($filterAction) {
    $query .= " AND a.action LIKE :act";
    $params[':act'] = "%$filterAction%";
}

$query .= " ORDER BY a.created_at DESC LIMIT 200";
$stmt = $db->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Fetch users for filter dropdown
$users = $db->query("SELECT id, name FROM users ORDER BY name ASC")->fetchAll();

$pageTitle = 'Audit Logs';
ob_start();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
    <div>
        <h2 class="section-title" style="margin-bottom: 4px;">Security Audit Trail</h2>
        <p style="font-size: 13px; color: var(--text-muted);">Historical tracking of all system-critical operations and overrides.</p>
    </div>
    <button class="btn btn-secondary" onclick="window.location.href='audit.php'">
        <i data-lucide="refresh-cw" style="width:14px; margin-right:8px; vertical-align:middle;"></i> Reset Filters
    </button>
</div>

<!-- Advanced Filters -->
<div class="card" style="padding: 24px; margin-bottom: 24px; border: 1px solid var(--border);">
    <form method="GET" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 20px; align-items: flex-end;">
        <div class="form-group" style="margin:0;">
            <label class="label">Filter by Actor (User)</label>
            <select name="user_id" class="select" style="width: 100%;">
                <option value="">All Identities</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $filterUser == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label class="label">Search Action / Event</label>
            <input type="text" name="action" class="input" placeholder="e.g. LOGIN, OVERRIDE" value="<?= htmlspecialchars($filterAction) ?>">
        </div>
        <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">Apply Forensic Filter</button>
    </form>
</div>

<div class="card" style="padding: 0; border: 1px solid var(--border);">
    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User Identity</th>
                    <th>Action Event</th>
                    <th>Target Object</th>
                    <th>IP / Source</th>
                    <th>Detailed Log Data</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $l): ?>
                <tr>
                    <td style="white-space: nowrap;">
                        <div style="font-size: 12px; font-weight: 700; color: var(--text-main);"><?= date('M d, Y', strtotime($l['created_at'])) ?></div>
                        <div style="font-size: 11px; color: var(--text-muted);"><?= date('H:i:s', strtotime($l['created_at'])) ?></div>
                    </td>
                    <td>
                        <?php if ($l['user_name']): ?>
                            <div style="font-weight: 700; font-size: 13px; color: var(--primary);"><?= htmlspecialchars($l['user_name']) ?></div>
                            <div style="font-size: 9px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;"><?= $l['user_role'] ?></div>
                        <?php else: ?>
                            <span style="color: #64748b; font-weight: 600; font-size: 12px; background: #f1f5f9; padding: 2px 8px; border-radius: 4px;">SYSTEM</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php 
                            $bg = '#f1f5f9'; $fg = '#475569';
                            if (strpos($l['action'], 'SUCCESS') !== false || strpos($l['action'], 'LOGIN') !== false) { $bg = '#ecfdf5'; $fg = '#059669'; }
                            if (strpos($l['action'], 'FAILED') !== false || strpos($l['action'], 'BLOCK') !== false) { $bg = '#fef2f2'; $fg = '#dc2626'; }
                            if (strpos($l['action'], 'OVERRIDE') !== false) { $bg = '#fff7ed'; $fg = '#d97706'; }
                        ?>
                        <span style="font-family: var(--font-mono); font-size: 10px; font-weight: 800; background: <?= $bg ?>; color: <?= $fg ?>; padding: 4px 10px; border-radius: 4px; border: 1px solid rgba(0,0,0,0.05);">
                            <?= $l['action'] ?>
                        </span>
                    </td>
                    <td>
                        <div style="font-size: 12px; font-weight: 600;"><?= $l['table_name'] ?></div>
                        <div style="font-size: 10px; color: var(--text-muted);">ID: #<?= $l['record_id'] ?? 'N/A' ?></div>
                    </td>
                    <td><span class="mono" style="font-size: 11px; font-weight: 600; color: #64748b;"><?= $l['ip_address'] ?></span></td>
                    <td>
                        <div style="font-size: 11px; color: var(--text-muted); max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; background: #f8fafc; padding: 4px 8px; border-radius: 4px; border: 1px solid #e2e8f0;" title='<?= htmlspecialchars($l['details']) ?>'>
                            <?= htmlspecialchars($l['details']) ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" style="padding: 48px; text-align: center; color: var(--text-muted);">No forensic records match your filter criteria.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
