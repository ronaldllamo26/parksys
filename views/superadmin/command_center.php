<?php
// views/superadmin/command_center.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_SUPERADMIN);

$db = Database::getConnection();

// Fetch live counts
$zones = $db->query("
    SELECT z.name, COUNT(s.id) as total, 
           (SELECT COUNT(*) FROM sessions sess JOIN slots sl ON sess.slot_id = sl.id WHERE sl.zone_id = z.id AND sess.status = 'active') as occupied
    FROM zones z
    JOIN slots s ON z.id = s.zone_id
    GROUP BY z.id
")->fetchAll();

$pageTitle = 'Command Center';
ob_start();
?>

<div style="display: flex; gap: 24px; height: calc(100vh - 180px);">
    
    <!-- Left Panel: Strategic Map & Lockdown -->
    <div style="flex: 2; display: flex; flex-direction: column; gap: 24px;">
        
        <!-- Lockdown Control -->
        <div class="card" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border: 1px solid #334155; padding: 32px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="color: #fff; font-size: 22px; font-weight: 800; margin-bottom: 8px;">Facility Lockdown Protocol</h2>
                    <p style="color: #94a3b8; font-size: 13px;">Instant global override of all entry/exit gate controllers.</p>
                </div>
                <button id="lockdown-btn" onclick="toggleLockdown()" class="btn" style="background: #ef4444; color: #fff; border: none; padding: 16px 32px; font-size: 16px; font-weight: 900; box-shadow: 0 0 20px rgba(239, 68, 68, 0.4); border-radius: 12px; transition: all 0.3s;">
                    INITIATE LOCKDOWN
                </button>
            </div>
            <div id="lockdown-status" style="margin-top: 24px; display: flex; align-items: center; gap: 12px; color: #10b981; font-weight: 700; font-size: 13px;">
                <span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; box-shadow: 0 0 10px #10b981;"></span>
                ALL GATE SYSTEMS OPERATIONAL (STANDBY)
            </div>
        </div>

        <!-- Heatmap Grid -->
        <div class="card" style="flex: 1; border: 1px solid var(--border); padding: 24px; overflow: hidden; position: relative;">
            <div style="font-weight: 800; margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
                <i data-lucide="map-pin"></i> Facility Occupancy Heatmap
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px;">
                <?php foreach($zones as $z): 
                    $pct = ($z['occupied'] / $z['total']) * 100;
                    $color = '#10b981'; // Green
                    if($pct > 50) $color = '#f59e0b'; // Amber
                    if($pct > 80) $color = '#ef4444'; // Red
                ?>
                <div class="zone-heat-card" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 16px; position: relative; overflow: hidden;">
                    <div style="position: absolute; bottom: 0; left: 0; height: 4px; width: <?= $pct ?>%; background: <?= $color ?>; transition: width 0.5s;"></div>
                    <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;"><?= $z['name'] ?></div>
                    <div style="font-size: 24px; font-weight: 900; margin: 10px 0;"><?= $z['occupied'] ?> <span style="font-size: 14px; color: #94a3b8;">/ <?= $z['total'] ?></span></div>
                    <div style="font-size: 11px; color: <?= $color ?>; font-weight: 700;"><?= round($pct) ?>% Capacity Used</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right Panel: AI Feed & Live Stats -->
    <div style="flex: 1; display: flex; flex-direction: column; gap: 24px;">
        
        <!-- Neural AI Log -->
        <div class="card" style="flex: 2; background: #000; border: 1px solid #333; color: #00ff00; font-family: 'Courier New', monospace; padding: 20px; overflow: hidden; position: relative;">
            <div style="position: absolute; top: 10px; right: 10px; font-size: 10px; color: #444;">AI_SIMULATOR_v4.2</div>
            <div style="margin-bottom: 15px; border-bottom: 1px solid #222; padding-bottom: 10px; font-size: 12px; font-weight: 700; color: #aaa;">NEURAL DETECTION FEED</div>
            <div id="ai-log" style="font-size: 12px; line-height: 1.6; overflow-y: auto; height: 100%; mask-image: linear-gradient(to top, transparent, black 20%);">
                <!-- Log entries injected by JS -->
            </div>
        </div>

        <!-- System Velocity Meter -->
        <div class="card" style="flex: 1; text-align: center; display: flex; flex-direction: column; justify-content: center; border: 1px solid var(--border);">
            <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 10px;">Traffic Velocity (Mins)</div>
            <div style="font-size: 48px; font-weight: 900; color: var(--primary);" id="velocity-meter">0.4</div>
            <div style="font-size: 11px; color: #10b981; font-weight: 700; margin-top: 8px;">NORMAL TRAFFIC FLOW</div>
        </div>

    </div>
</div>

<script>
let isLockdown = false;
function toggleLockdown() {
    isLockdown = !isLockdown;
    const btn = document.getElementById('lockdown-btn');
    const status = document.getElementById('lockdown-status');
    
    if (isLockdown) {
        btn.textContent = 'RELEASE LOCKDOWN';
        btn.style.background = '#1e293b';
        btn.style.boxShadow = '0 0 20px rgba(30, 41, 59, 0.4)';
        status.innerHTML = '<span style="width: 8px; height: 8px; background: #ef4444; border-radius: 50%; box-shadow: 0 0 10px #ef4444; animation: blink 0.5s infinite;"></span> CRITICAL: GLOBAL LOCKDOWN ACTIVE - ALL GATES SECURED';
        status.style.color = '#ef4444';
        addLogEntry("CRITICAL: GLOBAL LOCKDOWN SIGNAL EMITTED BY SUPERADMIN.");
        addLogEntry("GATES 01-08: CLOSED AND LOCKED.");
    } else {
        btn.textContent = 'INITIATE LOCKDOWN';
        btn.style.background = '#ef4444';
        btn.style.boxShadow = '0 0 20px rgba(239, 68, 68, 0.4)';
        status.innerHTML = '<span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; box-shadow: 0 0 10px #10b981;"></span> ALL GATE SYSTEMS OPERATIONAL (STANDBY)';
        status.style.color = '#10b981';
        addLogEntry("NOTICE: Global lockdown released. Gate controllers back online.");
    }
}

function addLogEntry(text) {
    const log = document.getElementById('ai-log');
    const entry = document.createElement('div');
    const timestamp = new Date().toLocaleTimeString();
    entry.innerHTML = `<span style="color: #555;">[${timestamp}]</span> ${text}`;
    log.insertBefore(entry, log.firstChild);
    if(log.childNodes.length > 50) log.removeChild(log.lastChild);
}

// Simulated AI Events
const aiEvents = [
    "Neural Network analyzing Zone A...",
    "License Plate detection service stable.",
    "Detected Plate: AAA-123. Match Confidence: 99.4%",
    "Anomaly detected in Slot #4. Re-evaluating occupancy...",
    "System sync complete. 104 gate nodes online.",
    "Updating facility heatmap data...",
    "Entry pattern optimization recommended for Zone B.",
    "Security scan: No unauthorized devices detected.",
    "Detected Plate: PHP-888. Access Level: EXECUTIVE.",
    "Slot #12 occupied. Mapping sensor data to DB..."
];

setInterval(() => {
    if(!isLockdown) {
        const randomEvent = aiEvents[Math.floor(Math.random() * aiEvents.length)];
        addLogEntry(randomEvent);
        // Randomly update velocity
        const velocity = (Math.random() * 0.8 + 0.1).toFixed(1);
        document.getElementById('velocity-meter').textContent = velocity;
    }
}, 4000);

// Initial logs
setTimeout(() => addLogEntry("ParkSys Pro AI Core booting..."), 500);
setTimeout(() => addLogEntry("Connecting to local Gate Controllers..."), 1200);
setTimeout(() => addLogEntry("Neural Plate Engine: STANDBY."), 2000);

</script>

<style>
@keyframes blink {
    0% { opacity: 1; }
    50% { opacity: 0.3; }
    100% { opacity: 1; }
}
.zone-heat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    transition: all 0.3s;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
