<?php
// api/process_exit.php

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

$identifier    = clean($_POST['identifier']     ?? '');
$paymentMethod = clean($_POST['payment_method'] ?? 'cash');
$isDiscounted  = (($_POST['apply_discount'] ?? 'false') === 'true');
$adminId       = (int) $_SESSION[SESSION_USER_ID];

$ctrl   = new SessionController();
$result = $ctrl->processExit($identifier, $adminId, $paymentMethod, $isDiscounted);

echo json_encode($result);