<?php
// views/superadmin/users.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_SUPERADMIN);

$db = Database::getConnection();

$users = $db->query("SELECT * FROM users ORDER BY role ASC, name ASC")->fetchAll();

$pageTitle = 'User Management';
ob_start();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h2 class="section-title">System Users</h2>
    <button class="btn btn-primary" onclick="openAddUser()">+ Add New User</button>
</div>

<div class="card" style="padding: 0;">
    <table class="table">
        <thead>
            <tr>
                <th>User Name</th>
                <th>Email Address</th>
                <th>Access Level</th>
                <th>Status</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td>
                    <div style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($u['name']) ?></div>
                </td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td>
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 4px 8px; border-radius: 4px; background: var(--primary-light); color: var(--primary);">
                        <?= $u['role'] ?>
                    </span>
                </td>
                <td>
                    <?php if ($u['is_active']): ?>
                        <span style="color: var(--success); display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 500;">
                            <span style="width: 6px; height: 6px; border-radius: 50%; background: var(--success);"></span> Active
                        </span>
                    <?php else: ?>
                        <span style="color: var(--danger); display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 500;">
                            <span style="width: 6px; height: 6px; border-radius: 50%; background: var(--danger);"></span> Inactive
                        </span>
                    <?php endif; ?>
                </td>
                <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                <td>
                    <button class="btn btn-secondary" style="padding: 6px 10px; font-size: 12px;">Edit</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add User Modal (Placeholder) -->
<div class="modal-overlay" id="user-modal" onclick="closeModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <h2 class="page-title" style="margin-bottom: 24px;">Add System User</h2>
        <form id="add-user-form">
            <div class="form-group">
                <label class="label">Full Name</label>
                <input type="text" class="input" placeholder="e.g. John Doe" required>
            </div>
            <div class="form-group">
                <label class="label">Email Address</label>
                <input type="email" class="input" placeholder="john@parksys.com" required>
            </div>
            <div class="form-group">
                <label class="label">Access Role</label>
                <select class="select">
                    <option value="admin">Admin (Staff)</option>
                    <option value="superadmin">Superadmin</option>
                </select>
            </div>
            <div class="form-group">
                <label class="label">Temporary Password</label>
                <input type="password" class="input" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; margin-top: 10px;">Create User Account</button>
        </form>
    </div>
</div>

<script>
function openAddUser() {
    document.getElementById('user-modal').classList.add('open');
}
function closeModal(e) {
    if (!e || e.target.classList.contains('modal-overlay'))
        document.getElementById('user-modal').classList.remove('open');
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
