<?php
// api/get_analytics_data.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json');
requireRole(ROLE_SUPERADMIN);

$db = Database::getConnection();

// 1. KPI Metrics
$kpis = $db->query("
    SELECT 
        (SELECT SUM(total_fee) FROM transactions) as total_revenue,
        (SELECT COUNT(*) FROM transactions) as total_transactions,
        (SELECT COUNT(*) FROM sessions WHERE status = 'active') as current_occupancy,
        (SELECT COUNT(*) FROM audit_logs WHERE action = 'SECURITY_BLOCK' OR action = 'EMERGENCY_OVERRIDE') as security_events
")->fetch(PDO::FETCH_ASSOC);

// 2. Revenue Trend (Last 30 Days)
$revenueTrend = $db->query("
    SELECT DATE(paid_at) as date, SUM(total_fee) as amount
    FROM transactions
    WHERE paid_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
    GROUP BY DATE(paid_at)
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// 3. Vehicle Distribution
$vehicleDist = $db->query("
    SELECT s.vehicle_type, COUNT(*) as count
    FROM transactions t
    JOIN sessions s ON t.session_id = s.id
    GROUP BY s.vehicle_type
")->fetchAll(PDO::FETCH_ASSOC);

// 4. Hourly Traffic (Peak Hours)
$peakHours = $db->query("
    SELECT HOUR(entry_time) as hour, COUNT(*) as count
    FROM sessions
    WHERE entry_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY HOUR(entry_time)
    ORDER BY hour ASC
")->fetchAll(PDO::FETCH_ASSOC);

// 5. Zone Utilization
$zoneUsage = $db->query("
    SELECT z.name, COUNT(s.id) as total_slots, 
           (SELECT COUNT(*) FROM sessions sess JOIN slots sl ON sess.slot_id = sl.id WHERE sl.zone_id = z.id AND sess.status = 'active') as occupied_slots
    FROM zones z
    JOIN slots s ON z.id = s.zone_id
    GROUP BY z.id
")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'kpis' => $kpis,
    'revenueTrend' => $revenueTrend,
    'vehicleDist' => $vehicleDist,
    'peakHours' => $peakHours,
    'zoneUsage' => $zoneUsage
]);
