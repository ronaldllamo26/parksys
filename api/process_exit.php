<?php
// api/process_exit.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../controllers/SessionController.php';

header('Content-Type: application/json');
requireRole(ROLE_ADMIN, ROLE_SUPERADMIN);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method Not Allowed'], 405);
}

$identifier    = clean($_POST['identifier']     ?? '');
$paymentMethod = clean($_POST['payment_method'] ?? 'cash');
$adminId       = (int) $_SESSION[SESSION_USER_ID];

$ctrl   = new SessionController();
$result = $ctrl->processExit($identifier, $adminId, $paymentMethod);

echo json_encode($result);