<?php
// api/validate_plate.php — AI Security & Dispatch Logic
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/constants.php';

$plate = strtoupper(trim($_GET['plate'] ?? ''));
$type  = $_GET['type'] ?? 'car';

if (!$plate) {
    jsonResponse(['success' => false, 'message' => 'Plate number is required'], 400);
}

$db = Database::getConnection();

// 1. Check if vehicle is already inside (Active session)
$stmt = $db->prepare("SELECT id FROM sessions WHERE plate_number = :p AND status = 'active' LIMIT 1");
$stmt->execute([':p' => $plate]);
$active = $stmt->fetch();

// 2. Simulated System Blacklist (Carnapped, Stolen, or Habitual Delinquents)
$blacklist = ['AAA-0000', 'CRIMINAL-1', 'BANNED-99', 'CAR-666'];
$isBlacklisted = in_array($plate, $blacklist);

// 3. Check for historical unpaid sessions (Completed but no transaction)
$stmt = $db->prepare("
    SELECT COUNT(*) as unpaid 
    FROM sessions s 
    LEFT JOIN transactions t ON s.id = t.session_id 
    WHERE s.plate_number = :p AND s.status = 'completed' AND t.id IS NULL
");
$stmt->execute([':p' => $plate]);
$unpaidCount = (int)($stmt->fetch()['unpaid'] ?? 0);

// 4. Smart Slot Recommendation Logic
// Prioritize slots that match the vehicle type exactly
$slotType = ($type === 'motorcycle') ? 'motorcycle' : 'standard';

$stmt = $db->prepare("
    SELECT s.id, s.slot_code, z.name as zone_name 
    FROM slots s 
    JOIN zones z ON s.zone_id = z.id 
    WHERE s.status = 'available' AND s.slot_type = :st 
    LIMIT 1
");
$stmt->execute([':st' => $slotType]);
$recSlot = $stmt->fetch();

// If no perfect match, find any available standard slot
if (!$recSlot) {
    $recSlot = $db->query("
        SELECT s.id, s.slot_code, z.name as zone_name 
        FROM slots s 
        JOIN zones z ON s.zone_id = z.id 
        WHERE s.status = 'available' 
        ORDER BY FIELD(s.slot_type, 'standard', 'vip', 'handicap', 'motorcycle') ASC 
        LIMIT 1
    ")->fetch();
}

// 5. Determine Security Insights
$riskLevel = 'LOW';
$insights  = 'Vehicle cleared. No security flags detected.';
$color     = '#10b981'; // Success Green

if ($active) {
    $riskLevel = 'HIGH';
    $insights  = 'DUPLICATE ENTRY DETECTED: This vehicle is already recorded as parked.';
    $color     = '#ef4444'; // Danger Red
    auditLog($db, $_SESSION[SESSION_USER_ID] ?? null, 'SECURITY_ALERT', 'sessions', null, ['plate' => $plate, 'reason' => 'Duplicate active session']);
} elseif ($isBlacklisted) {
    $riskLevel = 'CRITICAL';
    $insights  = 'SECURITY ALERT: This plate is on the system BLACKLIST. Deny entry and contact supervisor.';
    $color     = '#ef4444';
    auditLog($db, $_SESSION[SESSION_USER_ID] ?? null, 'SECURITY_BLOCK', 'sessions', null, ['plate' => $plate, 'reason' => 'Blacklisted vehicle']);
} elseif ($unpaidCount > 0) {
    $riskLevel = 'MEDIUM';
    $insights  = "NOTICE: This vehicle has $unpaidCount unpaid previous sessions. Collection required upon exit.";
    $color     = '#f59e0b'; // Warning Orange
}

jsonResponse([
    'success'    => true,
    'plate'      => $plate,
    'risk_level' => $riskLevel,
    'insights'   => $insights,
    'color'      => $color,
    'recommendation' => $recSlot ? [
        'id'    => $recSlot['id'],
        'label' => "{$recSlot['zone_name']} — {$recSlot['slot_code']}"
    ] : null
]);
