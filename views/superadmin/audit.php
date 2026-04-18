<?php
// views/superadmin/audit.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_SUPERADMIN);

$db = Database::getConnection();

$logs = $db->query("
    SELECT a.*, u.name as user_name, u.role as user_role
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 200
")->fetchAll();

$pageTitle = 'Audit Logs';
ob_start();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h2 class="section-title">System Activity Trail</h2>
    <div style="font-size: 13px; color: var(--text-muted);">Monitoring last 200 system events</div>
</div>

<div class="card" style="padding: 0;">
    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User / Identity</th>
                    <th>Action</th>
                    <th>Object / Table</th>
                    <th>IP Address</th>
                    <th>Event Metadata</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $l): ?>
                <tr>
                    <td>
                        <div style="font-size: 13px; font-weight: 500;"><?= date('M d, H:i:s', strtotime($l['created_at'])) ?></div>
                    </td>
                    <td>
                        <?php if ($l['user_name']): ?>
                            <div style="font-weight: 600; font-size: 13px;"><?= htmlspecialchars($l['user_name']) ?></div>
                            <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;"><?= $l['user_role'] ?></div>
                        <?php else: ?>
                            <span style="color: var(--text-muted); font-style: italic; font-size: 13px;">System / Public</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php 
                            $color = 'var(--text-muted)';
                            if (strpos($l['action'], 'SUCCESS') !== false) $color = 'var(--success)';
                            if (strpos($l['action'], 'FAILED') !== false) $color = 'var(--danger)';
                            if (strpos($l['action'], 'CREATED') !== false) $color = 'var(--primary)';
                        ?>
                        <span style="font-family: var(--font-mono); font-size: 11px; font-weight: 700; color: <?= $color ?>;">
                            <?= $l['action'] ?>
                        </span>
                    </td>
                    <td><span style="font-size: 13px;"><?= $l['table_name'] ?></span> <span style="font-size: 11px; color: var(--text-muted);">#<?= $l['record_id'] ?? 'N/A' ?></span></td>
                    <td><span class="mono" style="font-size: 11px;"><?= $l['ip_address'] ?></span></td>
                    <td>
                        <div style="font-size: 11px; color: var(--text-muted); max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title='<?= htmlspecialchars($l['details']) ?>'>
                            <?= htmlspecialchars($l['details']) ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
