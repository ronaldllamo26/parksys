<?php
// api/auth_login.php — AJAX Login Endpoint

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../controllers/AuthController.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method Not Allowed'], 405);
}

$email    = $_POST['email']    ?? '';
$password = $_POST['password'] ?? '';

$auth   = new AuthController();
$result = $auth->login($email, $password);

echo json_encode($result);