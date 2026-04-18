<?php
// force_login.php — Admin Bypass for Debugging
require_once 'config/database.php';
require_once 'config/constants.php';

session_start();

$db = Database::getConnection();

// Find the superadmin user
$stmt = $db->prepare("SELECT * FROM users WHERE email = 'admin@parksys.com' LIMIT 1");
$stmt->execute();
$user = $stmt->fetch();

if ($user) {
    // Manually set session variables
    $_SESSION[SESSION_USER_ID]   = $user['id'];
    $_SESSION[SESSION_USER_ROLE] = $user['role'];
    $_SESSION[SESSION_USER_NAME] = $user['name'];

    // Redirect to dashboard
    header('Location: views/admin/dashboard.php');
    exit;
} else {
    die("Error: Admin user 'admin@parksys.com' not found in the database. Please make sure you have imported the users table correctly.");
}
