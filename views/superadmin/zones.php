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

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
        <h2 class="section-title">Facility Layout</h2>
        <p style="font-size: 13px; color: var(--text-muted);">Configure parking zones and manage individual slot properties.</p>
    </div>
    <div style="display: flex; gap: 12px;">
        <button class="btn btn-secondary" onclick="alert('Batch add coming soon')">Batch Add Slots</button>
        <button class="btn btn-primary">+ Create New Zone</button>
    </div>
</div>

<div class="grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px;">
    <?php foreach ($zones as $z): ?>
    <div class="card" style="padding: 0; overflow: hidden;">
        <div style="padding: 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: var(--sidebar-hover);">
            <div>
                <div style="font-weight: 700; color: var(--text-main); font-size: 16px;"><?= htmlspecialchars($z['name']) ?></div>
                <div style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($z['floor']) ?> — <?= htmlspecialchars($z['description']) ?></div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 20px; font-weight: 800; color: var(--primary);"><?= $z['available_slots'] ?> <span style="font-size: 12px; color: var(--text-muted); font-weight: 400;">/ <?= $z['total_slots'] ?></span></div>
                <div style="font-size: 10px; font-weight: 700; color: var(--success); text-transform: uppercase;">Available Now</div>
            </div>
        </div>
        
        <div style="padding: 24px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                <div style="font-size: 13px; font-weight: 600;">Slot Occupancy</div>
                <div style="font-size: 13px; color: var(--muted);"><?= $z['total_slots'] > 0 ? round(($z['occupied_slots'] / $z['total_slots']) * 100) : 0 ?>% Full</div>
            </div>
            <div style="height: 8px; background: var(--border); border-radius: 4px; overflow: hidden; margin-bottom: 24px;">
                <div style="height: 100%; width: <?= $z['total_slots'] > 0 ? ($z['occupied_slots'] / $z['total_slots']) * 100 : 0 ?>%; background: var(--primary); transition: 0.5s;"></div>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <button class="btn btn-secondary" style="flex: 1; font-size: 12px; padding: 10px;">Edit Zone</button>
                <button class="btn btn-secondary" style="flex: 1; font-size: 12px; padding: 10px;" onclick="window.location.href='../admin/dashboard.php'">View Map</button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card" style="margin-top: 32px; padding: 0;">
    <div style="padding: 20px 24px; border-bottom: 1px solid var(--border); font-weight: 700;">Recent Slot Updates</div>
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
                <td><span class="mono" style="font-weight: 700;"><?= $r['slot_code'] ?></span></td>
                <td><?= $r['zone_name'] ?></td>
                <td><span style="font-size: 12px; text-transform: capitalize;"><?= $r['slot_type'] ?></span></td>
                <td>
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 4px 8px; border-radius: 4px; background: <?= $r['status'] === 'available' ? 'var(--primary-light)' : 'var(--sidebar-hover)' ?>; color: <?= $r['status'] === 'available' ? 'var(--primary)' : 'var(--text-muted)' ?>;">
                        <?= $r['status'] ?>
                    </span>
                </td>
                <td>Just now</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
