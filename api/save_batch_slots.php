<?php
// api/save_batch_slots.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');
requireRole(ROLE_SUPERADMIN);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method Not Allowed'], 405);
}

$zoneId   = (int) ($_POST['zone_id'] ?? 0);
$start    = clean($_POST['start_code'] ?? '');
$quantity = (int) ($_POST['quantity'] ?? 0);
$type     = clean($_POST['slot_type'] ?? 'standard');

if (!$zoneId || !$start || $quantity <= 0) {
    jsonResponse(['success' => false, 'message' => 'Missing required fields.']);
}

$db = Database::getConnection();

// Extract prefix and number from start code (e.g., "A01" -> "A", 1)
preg_match('/^([a-zA-Z-]+)(\d+)$/', $start, $matches);
if (!$matches) {
    jsonResponse(['success' => false, 'message' => 'Invalid starting code format (e.g., V01).']);
}

$prefix = $matches[1];
$startNum = (int) $matches[2];

try {
    $db->beginTransaction();

    $stmt = $db->prepare("INSERT INTO slots (zone_id, slot_code, slot_type, status) VALUES (:zid, :code, :type, 'available')");

    for ($i = 0; $i < $quantity; $i++) {
        $currentNum = $startNum + $i;
        $slotCode = $prefix . str_pad($currentNum, 2, '0', STR_PAD_LEFT);

        // Check for duplicates
        $check = $db->prepare("SELECT id FROM slots WHERE slot_code = :code");
        $check->execute([':code' => $slotCode]);
        if ($check->fetch()) continue; // Skip if exists

        $stmt->execute([
            ':zid' => $zoneId,
            ':code' => $slotCode,
            ':type' => $type
        ]);
    }

    $db->commit();
    jsonResponse(['success' => true, 'message' => "Successfully generated {$quantity} slots."]);

} catch (PDOException $e) {
    $db->rollBack();
    jsonResponse(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
