<?php
// api/get_slots.php — Returns all slots as JSON for live refresh

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');
requireRole(ROLE_ADMIN, ROLE_SUPERADMIN);

$db = Database::getConnection();

$slots = $db->query("
    SELECT sl.id, sl.slot_code, sl.status,
           s.plate_number, s.vehicle_type, s.entry_time, s.reference_id,
           TIMESTAMPDIFF(MINUTE, s.entry_time, NOW()) AS duration_mins
    FROM slots sl
    LEFT JOIN sessions s ON s.slot_id = sl.id AND s.status = 'active'
    ORDER BY sl.slot_code
")->fetchAll();

// Add formatted duration
foreach ($slots as &$slot) {
    if ($slot['duration_mins'] !== null) {
        $slot['duration_label'] = formatDuration((int) $slot['duration_mins']);
    }
}

echo json_encode(['success' => true, 'slots' => $slots]);