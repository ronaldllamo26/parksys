<?php
// views/superadmin/zones.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_SUPERADMIN);

$db = Database::getConnection();

// Fetch zones with slot counts
$zones = $db->query("
    SELECT z.*, 
           COUNT(s.id) as total_slots,
           SUM(s.status = 'available') as available_slots,
           SUM(s.status = 'occupied') as occupied_slots
    FROM zones z
    LEFT JOIN slots s ON z.id = s.zone_id
    GROUP BY z.id
    ORDER BY z.name ASC
")->fetchAll();

$pageTitle = 'Zones & Slots Architect';
ob_start();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
    <div>
        <h2 class="section-title" style="margin-bottom: 4px;">Facility Layout</h2>
        <p style="font-size: 13px; color: var(--text-muted);">Configure parking zones and manage individual slot properties.</p>
    </div>
    <div style="display: flex; gap: 12px;">
        <button class="btn btn-secondary" onclick="openBatchModal()">
            <i data-lucide="layers" style="width:14px; margin-right:6px; vertical-align:middle;"></i> Batch Add Slots
        </button>
        <button class="btn btn-primary" onclick="openZoneModal()">+ Create New Zone</button>
    </div>
</div>

<div class="grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px;">
    <?php foreach ($zones as $z): ?>
    <div class="card" style="padding: 0; overflow: hidden; border: 1px solid var(--border);">
        <div style="padding: 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: var(--sidebar-hover);">
            <div>
                <div style="font-weight: 800; color: var(--text-main); font-size: 16px; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="map-pin" style="width:16px; color: var(--primary);"></i>
                    <?= htmlspecialchars($z['name']) ?>
                </div>
                <div style="font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 2px;">
                    <?= htmlspecialchars($z['floor']) ?> — <?= htmlspecialchars($z['description']) ?>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 22px; font-weight: 900; color: var(--primary);"><?= $z['available_slots'] ?> <span style="font-size: 12px; color: var(--text-muted); font-weight: 400;">/ <?= $z['total_slots'] ?></span></div>
                <div style="font-size: 9px; font-weight: 800; color: var(--success); text-transform: uppercase; letter-spacing: 1px;">Available Now</div>
            </div>
        </div>
        
        <div style="padding: 24px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                <div style="font-size: 12px; font-weight: 700; color: var(--text-muted);">SLOT OCCUPANCY</div>
                <div style="font-size: 12px; font-weight: 800; color: var(--text-main);"><?= $z['total_slots'] > 0 ? round(($z['occupied_slots'] / $z['total_slots']) * 100) : 0 ?>% Full</div>
            </div>
            <div style="height: 10px; background: var(--bg); border-radius: 5px; overflow: hidden; margin-bottom: 24px; border: 1px solid var(--border);">
                <div style="height: 100%; width: <?= $z['total_slots'] > 0 ? ($z['occupied_slots'] / $z['total_slots']) * 100 : 0 ?>%; background: linear-gradient(90deg, var(--primary), #6366f1); transition: 0.6s cubic-bezier(0.4, 0, 0.2, 1);"></div>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <button class="btn btn-secondary" style="flex: 1; font-size: 11px; font-weight: 700; padding: 12px; border-color: var(--border);" onclick="openEditZoneModal('<?= $z['id'] ?>', '<?= addslashes($z['name']) ?>')">Edit Properties</button>
                <button class="btn btn-secondary" style="flex: 1; font-size: 11px; font-weight: 700; padding: 12px; border-color: var(--border);" onclick="window.location.href='<?= BASE_URL ?>/views/admin/dashboard.php'">Interactive Map</button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card" style="margin-top: 32px; padding: 0; border: 1px solid var(--border);">
    <div style="padding: 20px 24px; border-bottom: 1px solid var(--border); font-weight: 800; font-size: 14px; display: flex; align-items: center; gap: 10px;">
        <i data-lucide="history" style="width:16px; color: var(--primary);"></i>
        Recent Slot Updates
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Slot Code</th>
                <th>Zone</th>
                <th>Type</th>
                <th>Current Status</th>
                <th>Last Updated</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $recent = $db->query("SELECT s.*, z.name as zone_name FROM slots s JOIN zones z ON s.zone_id = z.id ORDER BY s.id DESC LIMIT 5")->fetchAll();
            foreach ($recent as $r):
            ?>
            <tr>
                <td><span class="mono" style="font-weight: 800; color: var(--primary);"><?= $r['slot_code'] ?></span></td>
                <td><div style="font-weight: 600;"><?= $r['zone_name'] ?></div></td>
                <td>
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">
                        <?= ucfirst($r['slot_type'] ?: 'standard') ?>
                    </span>
                </td>
                <td>
                    <span style="font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 4px 10px; border-radius: 20px; background: <?= $r['status'] === 'available' ? '#ecfdf5' : '#f1f5f9' ?>; color: <?= $r['status'] === 'available' ? '#059669' : '#64748b' ?>; border: 1px solid <?= $r['status'] === 'available' ? '#d1fae5' : '#e2e8f0' ?>;">
                        <?= $r['status'] ?>
                    </span>
                </td>
                <td style="font-size: 12px; color: var(--text-muted);">Just now</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add Zone Modal -->
<div class="modal-overlay" id="zone-modal" onclick="closeModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div style="text-align: center; margin-bottom: 32px;">
            <div style="width: 56px; height: 56px; background: var(--primary-light); color: var(--primary); border-radius: 14px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <i data-lucide="map" style="width:28px; height:28px;"></i>
            </div>
            <h2 style="font-size: 20px; font-weight: 800;">Create Parking Zone</h2>
            <p style="font-size: 13px; color: var(--text-muted);">Define a new physical area for vehicle parking.</p>
        </div>
        <form id="add-zone-form">
            <div class="form-group">
                <label class="label">Zone Name</label>
                <input type="text" class="input" placeholder="e.g. Zone D" required>
            </div>
            <div class="form-group">
                <label class="label">Floor Level</label>
                <input type="text" class="input" placeholder="e.g. 3rd Floor" required>
            </div>
            <div class="form-group">
                <label class="label">Description / Landmarks</label>
                <textarea class="input" style="height: 80px;" placeholder="Near the elevator, South Wing..."></textarea>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 24px;">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Zone</button>
            </div>
        </form>
    </div>
</div>

<!-- Batch Add Modal -->
<div class="modal-overlay" id="batch-modal" onclick="closeModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div style="text-align: center; margin-bottom: 32px;">
            <div style="width: 56px; height: 56px; background: #eff6ff; color: #2563eb; border-radius: 14px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <i data-lucide="layers" style="width:28px; height:28px;"></i>
            </div>
            <h2 style="font-size: 20px; font-weight: 800;">Batch Slot Architect</h2>
            <p style="font-size: 13px; color: var(--text-muted);">Quickly generate multiple slots for a zone.</p>
        </div>
        <form id="batch-slot-form">
            <div class="form-group">
                <label class="label">Target Zone</label>
                <select class="select" style="width: 100%;">
                    <?php foreach ($zones as $z): ?>
                        <option value="<?= $z['id'] ?>"><?= $z['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="label">Starting Code</label>
                    <input type="text" class="input" placeholder="C01" required>
                </div>
                <div class="form-group">
                    <label class="label">Slot Quantity</label>
                    <input type="number" class="input" value="10" min="1" max="100" required>
                </div>
            </div>
            <div class="form-group">
                <label class="label">Vehicle Compatibility</label>
                <select class="select" style="width: 100%;">
                    <option value="standard">Standard Car</option>
                    <option value="motorcycle">Motorcycle</option>
                    <option value="handicap">Handicap (Accessible)</option>
                    <option value="vip">VIP / Reserved</option>
                </select>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 24px;">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Generate Slots</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Zone Modal -->
<div class="modal-overlay" id="edit-zone-modal" onclick="closeModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div style="text-align: center; margin-bottom: 32px;">
            <div style="width: 56px; height: 56px; background: #f1f5f9; color: var(--text-main); border-radius: 14px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <i data-lucide="settings-2" style="width:28px; height:28px;"></i>
            </div>
            <h2 style="font-size: 20px; font-weight: 800;" id="edit-modal-title">Edit Zone Properties</h2>
            <p style="font-size: 13px; color: var(--text-muted);">Adjust the configuration for this parking area.</p>
        </div>
        <form id="edit-zone-form">
            <input type="hidden" id="edit-zone-id">
            <div class="form-group">
                <label class="label">Zone Name</label>
                <input type="text" class="input" id="edit-zone-name" required>
            </div>
            <div class="form-group">
                <label class="label">Status</label>
                <select class="select" style="width: 100%;">
                    <option value="active">Active (Online)</option>
                    <option value="maintenance">Maintenance (Offline)</option>
                </select>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 24px;">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Properties</button>
            </div>
        </form>
    </div>
</div>

<script>
function openZoneModal() { document.getElementById('zone-modal').classList.add('open'); }
function openBatchModal() { document.getElementById('batch-modal').classList.add('open'); }
function openEditZoneModal(id, name) { 
    document.getElementById('edit-zone-id').value = id;
    document.getElementById('edit-zone-name').value = name;
    document.getElementById('edit-modal-title').textContent = 'Configuring ' + name;
    document.getElementById('edit-zone-modal').classList.add('open'); 
}

function closeModal(e) {
    if (!e || e.target.classList.contains('modal-overlay')) {
        document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('open'));
    }
}

// Handle Batch Slot Generation
document.getElementById('batch-slot-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData();
    formData.append('zone_id', this.querySelector('select').value);
    formData.append('start_code', this.querySelectorAll('input')[0].value);
    formData.append('quantity', this.querySelectorAll('input')[1].value);
    formData.append('slot_type', this.querySelectorAll('select')[1].value);

    fetch('<?= BASE_URL ?>/api/save_batch_slots.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert(res.message);
            location.reload();
        } else {
            alert(res.message);
        }
    });
});

// Handle Zone Creation
document.getElementById('add-zone-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData();
    formData.append('name', this.querySelectorAll('input')[0].value);
    formData.append('floor', this.querySelectorAll('input')[1].value);
    formData.append('description', this.querySelector('textarea').value);

    fetch('<?= BASE_URL ?>/api/save_zone.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert(res.message);
            location.reload();
        } else {
            alert(res.message);
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
