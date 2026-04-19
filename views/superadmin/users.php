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
                <th>Plate Number</th>
                <th>Access Level</th>
                <th>Membership</th>
                <th>Balance</th>
                <th>Loyalty</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td>
                    <div style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($u['name']) ?></div>
                    <div style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($u['email']) ?></div>
                </td>
                <td class="mono" style="font-weight: 700;"><?= $u['plate_number'] ?: '—' ?></td>
                <td>
                    <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; padding: 2px 6px; border-radius: 4px; background: #f1f5f9; color: #475569;">
                        <?= $u['role'] ?>
                    </span>
                </td>
                <td>
                    <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; padding: 2px 6px; border-radius: 4px; background: <?= $u['membership_type'] === 'vip' ? 'var(--primary-light)' : '#f1f5f9' ?>; color: <?= $u['membership_type'] === 'vip' ? 'var(--primary)' : '#475569' ?>;">
                        <?= $u['membership_type'] ?>
                    </span>
                </td>
                <td style="font-weight: 600;"><?= peso($u['wallet_balance']) ?></td>
                <td style="font-weight: 600; color: #f59e0b;">
                    <div style="display:flex; align-items:center; gap:4px;">
                        <i data-lucide="award" style="width:12px;"></i>
                        <?= number_format($u['loyalty_points']) ?>
                    </div>
                </td>
                <td>
                    <?php if ($u['is_active']): ?>
                        <span style="color: var(--success); display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 500;">
                            <span style="width: 5px; height: 5px; border-radius: 50%; background: var(--success);"></span> Active
                        </span>
                    <?php else: ?>
                        <span style="color: var(--danger); display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 500;">
                            <span style="width: 5px; height: 5px; border-radius: 50%; background: var(--danger);"></span> Inactive
                        </span>
                    <?php endif; ?>
                </td>
                <td>
                    <button class="btn btn-secondary" style="padding: 4px 8px; font-size: 11px; font-weight: 700;" 
                            onclick="openEditUser('<?= $u['id'] ?>', '<?= addslashes($u['name']) ?>', '<?= $u['email'] ?>', '<?= $u['role'] ?>', '<?= $u['plate_number'] ?>', '<?= $u['membership_type'] ?>', '<?= $u['wallet_balance'] ?>', '<?= $u['loyalty_points'] ?>')">
                        Edit
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
                <input type="text" class="input" id="add-name" placeholder="e.g. John Doe" required>
            </div>
            <div class="form-group">
                <label class="label">Email Address</label>
                <input type="email" class="input" id="add-email" placeholder="john@parksys.com" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="label">Access Role</label>
                    <select class="select" id="add-role" style="width: 100%;">
                        <option value="customer">Customer</option>
                        <option value="admin">Admin (Staff)</option>
                        <option value="superadmin">Superadmin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label">Membership Type</label>
                    <select class="select" id="add-membership" style="width: 100%;">
                        <option value="standard">Standard</option>
                        <option value="vip">VIP Member</option>
                    </select>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="label">Register Plate</label>
                    <input type="text" class="input mono" id="add-plate" placeholder="ABC-1234">
                </div>
                <div class="form-group">
                    <label class="label">Temporary Password</label>
                    <input type="password" class="input" id="add-password" placeholder="••••••••" required>
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
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="label">Access Role</label>
                    <select class="select" id="edit-user-role" style="width: 100%;">
                        <option value="customer">Customer</option>
                        <option value="admin">Admin (Staff)</option>
                        <option value="superadmin">Superadmin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label">Membership</label>
                    <select class="select" id="edit-user-membership" style="width: 100%;">
                        <option value="standard">Standard</option>
                        <option value="vip">VIP Member</option>
                    </select>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="label">Plate Number</label>
                    <input type="text" class="input mono" id="edit-user-plate" placeholder="ABC-1234">
                </div>
                <div class="form-group">
                    <label class="label">Wallet Balance (₱)</label>
                    <input type="number" step="0.01" class="input" id="edit-user-balance">
                </div>
            </div>
            <div class="form-group">
                <label class="label">Loyalty Points</label>
                <input type="number" class="input" id="edit-user-loyalty">
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
function openEditUser(id, name, email, role, plate, membership, balance, loyalty) {
    document.getElementById('edit-user-id').value = id;
    document.getElementById('edit-user-name').value = name;
    document.getElementById('edit-user-email').value = email;
    document.getElementById('edit-user-role').value = role;
    document.getElementById('edit-user-plate').value = plate || '';
    document.getElementById('edit-user-membership').value = membership;
    document.getElementById('edit-user-balance').value = balance;
    document.getElementById('edit-user-loyalty').value = loyalty || 0;
    document.getElementById('edit-user-modal').classList.add('open');
}

function closeModal(e) {
    if (!e || e.target.classList.contains('modal-overlay')) {
        document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('open'));
    }
}

// Form Submissions
document.getElementById('add-user-form').onsubmit = function(e) {
    e.preventDefault();
    const fd = new FormData();
    fd.append('name', document.getElementById('add-name').value);
    fd.append('email', document.getElementById('add-email').value);
    fd.append('role', document.getElementById('add-role').value);
    fd.append('membership', document.getElementById('add-membership').value);
    fd.append('plate', document.getElementById('add-plate').value);
    fd.append('password', document.getElementById('add-password').value);
    
    saveUser(fd);
};

document.getElementById('edit-user-form').onsubmit = function(e) {
    e.preventDefault();
    const fd = new FormData();
    fd.append('id', document.getElementById('edit-user-id').value);
    fd.append('name', document.getElementById('edit-user-name').value);
    fd.append('email', document.getElementById('edit-user-email').value);
    fd.append('role', document.getElementById('edit-user-role').value);
    fd.append('membership', document.getElementById('edit-user-membership').value);
    fd.append('plate', document.getElementById('edit-user-plate').value);
    fd.append('balance', document.getElementById('edit-user-balance').value);
    fd.append('loyalty', document.getElementById('edit-user-loyalty').value);
    
    saveUser(fd);
};

function saveUser(fd) {
    fetch('<?= BASE_URL ?>/api/save_user.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert(res.message);
                window.location.reload();
            } else {
                alert(res.message);
            }
        });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
