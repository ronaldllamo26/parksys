<?php
// views/admin/entry.php — Advanced AI-Automated Entry
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_ADMIN, ROLE_SUPERADMIN);

$db = Database::getConnection();

// Get available slots for recommendation
$slots = $db->query("
    SELECT s.*, z.name as zone_name 
    FROM slots s 
    JOIN zones z ON s.zone_id = z.id 
    WHERE s.status = 'available' 
    ORDER BY z.name ASC, s.slot_code ASC
")->fetchAll();

$pageTitle = 'Automated Entry';
ob_start();
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px;">
        <div>
            <h2 class="section-title">New Parking Session</h2>
            <p style="font-size: 13px; color: var(--text-muted);">AI-assisted vehicle registration and slot assignment.</p>
        </div>
        <div style="display: flex; gap: 12px;">
            <button class="btn btn-secondary" onclick="simulateAIScan()" style="background: var(--primary-light); color: var(--primary); border-color: var(--primary);">
                <i data-lucide="scan-face" style="width:16px; margin-right:8px;"></i> Simulate AI Scan
            </button>
        </div>
    </div>

    <div class="card">
        <form id="entry-form">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">
                <!-- Left: Vehicle Info -->
                <div>
                    <div class="form-group">
                        <label class="label">Plate Number</label>
                        <div style="position: relative;">
                            <input type="text" class="input mono" id="plate" placeholder="e.g. ABC-1234" style="text-transform: uppercase; font-size: 18px; font-weight: 700; letter-spacing: 0.05em; padding-right: 40px;" required>
                            <i data-lucide="camera" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); width: 18px; color: var(--primary); cursor: pointer;" onclick="simulateAIScan()" title="Scan Camera"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="label">Vehicle Category</label>
                        <select class="select" id="vtype">
                            <option value="car">Car / SUV / Sedan</option>
                            <option value="motorcycle">Motorcycle / Scooter</option>
                            <option value="van">Van / Commercial Truck</option>
                        </select>
                    </div>
                </div>

                <!-- Right: Slot Selection -->
                <div>
                    <div class="form-group">
                        <label class="label">Assign Parking Slot</label>
                        <select class="select" id="slot_id" required>
                            <option value="">-- Choose available slot --</option>
                            <?php foreach ($slots as $s): ?>
                                <option value="<?= $s['id'] ?>">
                                    <?= $s['zone_name'] ?> — <?= $s['slot_code'] ?> (<?= ucfirst($s['slot_type']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn" onclick="recommendSlot()" style="width: 100%; margin-top: 12px; font-size: 12px; padding: 8px; border: 1px dashed var(--primary); background: transparent; color: var(--primary);">
                            <i data-lucide="sparkles" style="width:14px; margin-right:6px;"></i> Use Smart Recommendation
                        </button>
                    </div>
                </div>
            </div>

            <div id="ai-feedback" style="display: none; padding: 16px; background: var(--primary-light); border-radius: 8px; margin-bottom: 24px; border: 1px solid var(--primary);">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i data-lucide="cpu" style="color: var(--primary);"></i>
                    <div>
                        <div style="font-size: 13px; font-weight: 700; color: var(--primary);">AI ENGINE DETECTED</div>
                        <div style="font-size: 11px; color: var(--primary);" id="ai-text">Plate recognized. Best slot suggested.</div>
                    </div>
                </div>
            </div>

            <div id="alert" class="alert"></div>

            <div style="margin-top: 32px; padding-top: 32px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 16px;">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='dashboard.php'">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submit-btn">
                    <i data-lucide="check-circle" style="width:18px; margin-right:8px;"></i> Confirm Check-in
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function simulateAIScan() {
    const feedback = document.getElementById('ai-feedback');
    const plateInput = document.getElementById('plate');
    const vType = document.getElementById('vtype');
    const aitext = document.getElementById('ai-text');
    
    // Random Plate Simulation
    const letters = "ABCDEFGHJKLMNPQRSTUVWXYZ";
    const nums = "0123456789";
    let p = "";
    for(let i=0; i<3; i++) p += letters.charAt(Math.floor(Math.random()*letters.length));
    p += "-";
    for(let i=0; i<4; i++) p += nums.charAt(Math.floor(Math.random()*nums.length));
    
    plateInput.value = p;
    vType.value = Math.random() > 0.7 ? (Math.random() > 0.5 ? 'van' : 'motorcycle') : 'car';
    
    feedback.style.display = 'block';
    aitext.textContent = `Autonomous Detection: ${p} recognized as ${vType.value.toUpperCase()}.`;
    
    recommendSlot();
}

function recommendSlot() {
    const select = document.getElementById('slot_id');
    if (select.options.length > 1) {
        select.selectedIndex = 1; // Pick the first available
        const feedback = document.getElementById('ai-feedback');
        feedback.style.display = 'block';
    }
}

document.getElementById('entry-form').onsubmit = function(e) {
    e.preventDefault();
    const btn = document.getElementById('submit-btn');
    const alert = document.getElementById('alert');
    
    btn.disabled = true;
    btn.textContent = 'Processing Entry...';
    
    const fd = new FormData();
    fd.append('slot_id', document.getElementById('slot_id').value);
    fd.append('plate_number', document.getElementById('plate').value);
    fd.append('vehicle_type', document.getElementById('vtype').value);
    
    fetch('<?= BASE_URL ?>/api/process_entry.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert.textContent = 'Entry recorded! Redirecting...';
                alert.className = 'alert alert-success show';
                setTimeout(() => window.location.href = 'dashboard.php', 1000);
            } else {
                btn.disabled = false;
                btn.textContent = 'Confirm Check-in';
                alert.textContent = res.message;
                alert.className = 'alert alert-danger show';
            }
        });
};
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
