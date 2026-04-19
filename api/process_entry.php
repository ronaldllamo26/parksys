<?php
// api/process_entry.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../controllers/SessionController.php';

header('Content-Type: application/json');
requireRole(ROLE_ADMIN, ROLE_SUPERADMIN);
requireCsrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method Not Allowed'], 405);
}

$slotId  = (int)   ($_POST['slot_id']      ?? 0);
$plate   = clean(   $_POST['plate_number'] ?? '');
$vtype   = clean(   $_POST['vehicle_type'] ?? '');
$userId  = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;
$adminId = (int) $_SESSION[SESSION_USER_ID];

$ctrl   = new SessionController();
$result = $ctrl->processEntry($slotId, $plate, $vtype, $adminId, $userId);

echo json_encode($result);