<?php
// api/get_dashboard_state.php — Unified Real-time Data Hub
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION[SESSION_USER_ID])) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$db = Database::getConnection();
$uid = $_SESSION[SESSION_USER_ID];

// 1. Overall Stats
$stats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
        SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied
    FROM slots
")->fetch();

$types = $db->query("
    SELECT 
        SUM(CASE WHEN slot_type = 'motorcycle' AND status = 'occupied' THEN 1 ELSE 0 END) as motor,
        SUM(CASE WHEN slot_type != 'motorcycle' AND status = 'occupied' THEN 1 ELSE 0 END) as cars
    FROM slots
")->fetch();

// 2. My Shift Performance
$shift = $db->prepare("
    SELECT COALESCE(SUM(total_fee), 0) as revenue, COUNT(*) as count 
    FROM transactions t
    JOIN sessions s ON t.session_id = s.id
    WHERE s.processed_by = :uid AND DATE(t.paid_at) = CURDATE()
");
$shift->execute([':uid' => $uid]);
$shiftData = $shift->fetch();

// 3. Live Activity Feed (Combined Entry/Exit)
$activity = $db->query("
    (SELECT 'ENTRY' as type, plate_number, entry_time as time FROM sessions WHERE DATE(entry_time) = CURDATE() AND status = 'active')
    UNION ALL
    (SELECT 'EXIT' as type, plate_number, exit_time as time FROM sessions WHERE DATE(exit_time) = CURDATE() AND status = 'completed')
    ORDER BY time DESC LIMIT 5
")->fetchAll();

// 4. Grid State
$slots = $db->query("
    SELECT sl.id, sl.slot_code, sl.status, sl.slot_type, z.name as zone_name,
           s.plate_number, s.entry_time
    FROM slots sl
    JOIN zones z ON sl.zone_id = z.id
    LEFT JOIN sessions s ON s.slot_id = sl.id AND s.status = 'active'
    ORDER BY z.name, sl.slot_code
")->fetchAll();

// 5. AI Insights Engine (Operational Intelligence)
$insights = [];
$occupancyRate = ($stats['total'] > 0) ? ($stats['occupied'] / $stats['total']) : 0;

if ($occupancyRate > 0.85) {
    $insights[] = "CRITICAL: Occupancy at " . round($occupancyRate * 100) . "%. Activate overflow zones.";
} elseif ($occupancyRate > 0.6) {
    $insights[] = "ADVICE: Zone A is filling up. Suggest redirecting entries to Zone B.";
} else {
    $insights[] = "OPTIMAL: Parking flow is stable. Current load: " . round($occupancyRate * 100) . "%.";
}

// Peak hour simulation
$hourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
$stmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE paid_at > :t");
$stmt->execute([':t' => $hourAgo]);
$recentTxns = (int)$stmt->fetchColumn();
if ($recentTxns > 5) {
    $insights[] = "PEAK ALERT: High volume detected. Expect congestion at exit lanes.";
}

// 6. Hardware Health Monitoring (Simulated Sensors)
$hardware = [
    ['name' => 'Entry Gate #1', 'status' => 'Online', 'color' => '#10b981'],
    ['name' => 'Exit Gate #1', 'status' => 'Online', 'color' => '#10b981'],
    ['name' => 'Thermal Printer', 'status' => (rand(0, 10) > 8 ? 'Low Paper' : 'Online'), 'color' => (rand(0, 10) > 8 ? '#f59e0b' : '#10b981')],
];

jsonResponse([
    'success' => true,
    'stats' => [
        'available' => (int)$stats['available'],
        'occupied'  => (int)$stats['occupied'],
        'cars'      => (int)$types['cars'],
        'motor'     => (int)$types['motor'],
        'revenue'   => (float)$shiftData['revenue'],
        'shift_txns'=> (int)$shiftData['count']
    ],
    'activity' => array_map(function($a) {
        return [
            'type' => $a['type'],
            'plate' => $a['plate_number'],
            'time' => date('H:i', strtotime($a['time']))
        ];
    }, $activity),
    'slots' => $slots,
    'ai_insights' => $insights,
    'hardware' => $hardware
]);
