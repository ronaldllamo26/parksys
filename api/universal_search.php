<?php
// api/universal_search.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
if (strlen($query) < 2) {
    echo json_encode(['success' => true, 'results' => []]);
    exit;
}

$db = Database::getConnection();
$results = [];
$q = "%$query%";

// 1. Search Active Sessions (Plates)
$sessions = $db->prepare("SELECT plate_number as title, 'Active Session' as type, CONCAT('/views/admin/dashboard.php?plate=', plate_number) as link FROM sessions WHERE plate_number LIKE :q AND status = 'active' LIMIT 5");
$sessions->execute([':q' => $q]);
$results = array_merge($results, $sessions->fetchAll(PDO::FETCH_ASSOC));

// 2. Search Transactions
$txns = $db->prepare("SELECT plate_number as title, 'Transaction History' as type, '/views/admin/transactions.php' as link FROM transactions WHERE plate_number LIKE :q OR id LIKE :q LIMIT 5");
$txns->execute([':q' => $q]);
$results = array_merge($results, $txns->fetchAll(PDO::FETCH_ASSOC));

// 3. Search Users
$users = $db->prepare("SELECT name as title, CONCAT('User: ', role) as type, '/views/superadmin/users.php' as link FROM users WHERE name LIKE :q OR email LIKE :q LIMIT 3");
$users->execute([':q' => $q]);
$results = array_merge($results, $users->fetchAll(PDO::FETCH_ASSOC));

// 4. Search Zones
$zones = $db->prepare("SELECT name as title, 'Facility Zone' as type, '/views/superadmin/zones.php' as link FROM zones WHERE name LIKE :q LIMIT 2");
$zones->execute([':q' => $q]);
$results = array_merge($results, $zones->fetchAll(PDO::FETCH_ASSOC));

echo json_encode(['success' => true, 'results' => $results]);
