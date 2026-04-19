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
                    <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px; font-weight: 700;" 
                            onclick="openEditUser('<?= $u['id'] ?>', '<?= addslashes($u['name']) ?>', '<?= $u['email'] ?>', '<?= $u['role'] ?>')">
                        <i data-lucide="edit-2" style="width:12px; margin-right:4px; vertical-align:middle;"></i> Edit
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add User Modal -->
<div class="modal-overlay" id="user-modal" onclick="closeModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div style="text-align: center; margin-bottom: 32px;">
            <div style="width: 56px; height: 56px; background: var(--primary-light); color: var(--primary); border-radius: 14px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <i data-lucide="user-plus" style="width:28px; height:28px;"></i>
            </div>
            <h2 style="font-size: 20px; font-weight: 800; color: var(--text-main); margin-bottom: 4px;">Add System User</h2>
            <p style="font-size: 13px; color: var(--text-muted);">Create a new access account for staff or admin.</p>
        </div>

        <form id="add-user-form">
            <div class="form-group">
                <label class="label">Full Name</label>
                <input type="text" class="input" placeholder="e.g. John Doe" required>
            </div>
            <div class="form-group">
                <label class="label">Email Address</label>
                <input type="email" class="input" placeholder="john@parksys.com" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="label">Access Role</label>
                    <select class="select" style="width: 100%;">
                        <option value="admin">Admin (Staff)</option>
                        <option value="superadmin">Superadmin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label">Temporary Password</label>
                    <input type="password" class="input" placeholder="••••••••" required>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 24px;">
                <button type="button" onclick="closeModal()" class="btn" style="background: var(--bg); border: 1px solid var(--border); color: var(--text-muted); font-weight: 600;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="font-weight: 700;">Create Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal-overlay" id="edit-user-modal" onclick="closeModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div style="text-align: center; margin-bottom: 32px;">
            <div style="width: 56px; height: 56px; background: #f1f5f9; color: var(--text-main); border-radius: 14px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <i data-lucide="user-cog" style="width:28px; height:28px;"></i>
            </div>
            <h2 style="font-size: 20px; font-weight: 800; color: var(--text-main); margin-bottom: 4px;">Update User Account</h2>
            <p style="font-size: 13px; color: var(--text-muted);">Modify access rights or account details.</p>
        </div>

        <form id="edit-user-form">
            <input type="hidden" id="edit-user-id">
            <div class="form-group">
                <label class="label">Full Name</label>
                <input type="text" class="input" id="edit-user-name" required>
            </div>
            <div class="form-group">
                <label class="label">Email Address</label>
                <input type="email" class="input" id="edit-user-email" required>
            </div>
            <div class="form-group">
                <label class="label">Access Role</label>
                <select class="select" id="edit-user-role" style="width: 100%;">
                    <option value="admin">Admin (Staff)</option>
                    <option value="superadmin">Superadmin</option>
                </select>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 24px;">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary" style="font-weight: 700;">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddUser() { document.getElementById('user-modal').classList.add('open'); }
function openEditUser(id, name, email, role) {
    document.getElementById('edit-user-id').value = id;
    document.getElementById('edit-user-name').value = name;
    document.getElementById('edit-user-email').value = email;
    document.getElementById('edit-user-role').value = role;
    document.getElementById('edit-user-modal').classList.add('open');
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
