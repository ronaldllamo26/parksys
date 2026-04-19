<?php
// api/log_emergency.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json');
requireRole(ROLE_ADMIN, ROLE_SUPERADMIN);

$db = Database::getConnection();
$uid = $_SESSION[SESSION_USER_ID];

auditLog($db, $uid, 'EMERGENCY_OVERRIDE', 'system', null, [
    'action' => 'GATES_FORCE_OPEN',
    'timestamp' => date('Y-m-d H:i:s')
]);

echo json_encode(['success' => true, 'message' => 'Emergency event recorded']);
