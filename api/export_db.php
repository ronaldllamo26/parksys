<?php
// api/export_db.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireRole(ROLE_SUPERADMIN);

$db = Database::getConnection();

// Verify Password for High-Security Export
if (!isset($_POST['verify_password'])) {
    die("Unauthorized Access: Security Verification Required.");
}

$uid = $_SESSION[SESSION_USER_ID];
$user = $db->prepare("SELECT password FROM users WHERE id = :id");
$user->execute([':id' => $uid]);
$pwdHash = $user->fetchColumn();

if (!password_verify($_POST['verify_password'], $pwdHash)) {
    header("Location: ../views/superadmin/settings.php?security_error=1");
    exit;
}

// Simple SQL Export logic
$tables = ['users', 'zones', 'slots', 'sessions', 'transactions', 'audit_logs', 'system_settings', 'rates'];
$output = "-- ParkSys Pro Database Backup\n";
$output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

foreach ($tables as $table) {
    // 1. Export Table Structure
    $stmt = $db->query("SHOW CREATE TABLE $table");
    $createTable = $stmt->fetch(PDO::FETCH_ASSOC);
    $output .= "-- Table structure for: $table\n";
    $output .= "DROP TABLE IF EXISTS $table;\n";
    $output .= $createTable['Create Table'] . ";\n\n";

    // 2. Export Table Data
    $rows = $db->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
    $output .= "-- Dumping data for table: $table\n";
    foreach ($rows as $row) {
        $keys = array_keys($row);
        $values = array_map(function($v) use ($db) {
            return $v === null ? 'NULL' : $db->quote($v);
        }, array_values($row));
        
        $output .= "INSERT INTO $table (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n";
    }
    $output .= "\n-- --------------------------------------------------------\n\n";
}

header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="parksys_backup_' . date('Ymd_His') . '.sql"');
echo $output;
exit;
