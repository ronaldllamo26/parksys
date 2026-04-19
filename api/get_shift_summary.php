<?php
// api/get_shift_summary.php — Performance & Collections Report
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION[SESSION_USER_ID])) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$db = Database::getConnection();
$uid = $_SESSION[SESSION_USER_ID];

// 1. Fetch Revenue & Transaction Counts
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_txns,
        COALESCE(SUM(total_fee), 0) as total_revenue,
        COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN total_fee ELSE 0 END), 0) as cash_total,
        COALESCE(SUM(CASE WHEN payment_method = 'gcash' THEN total_fee ELSE 0 END), 0) as gcash_total,
        COALESCE(SUM(CASE WHEN payment_method = 'maya' THEN total_fee ELSE 0 END), 0) as maya_total,
        COALESCE(SUM(CASE WHEN payment_method = 'card' THEN total_fee ELSE 0 END), 0) as card_total
    FROM transactions t
    JOIN sessions s ON t.session_id = s.id
    WHERE s.processed_by = :uid AND DATE(t.paid_at) = CURDATE()
");
$stmt->execute([':uid' => $uid]);
$summary = $stmt->fetch();

// 2. Fetch Active Entries Processed
$stmt = $db->prepare("
    SELECT COUNT(*) as entries 
    FROM sessions 
    WHERE processed_by = :uid AND DATE(entry_time) = CURDATE()
");
$stmt->execute([':uid' => $uid]);
$totalEntries = (int)($stmt->fetch()['entries'] ?? 0);

jsonResponse([
    'success' => true,
    'staff' => $_SESSION[SESSION_USER_NAME],
    'role' => $_SESSION[SESSION_USER_ROLE],
    'date' => date('F d, Y'),
    'metrics' => [
        'total_revenue' => (float)$summary['total_revenue'],
        'total_transactions' => (int)$summary['total_txns'],
        'total_entries' => $totalEntries,
    ],
    'breakdown' => [
        'cash' => (float)$summary['cash_total'],
        'gcash' => (float)$summary['gcash_total'],
        'maya' => (float)$summary['maya_total'],
        'card' => (float)$summary['card_total'],
    ]
]);
