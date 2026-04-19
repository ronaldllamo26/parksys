<?php
// controllers/SessionController.php — Entry / Exit Processing

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../controllers/BillingController.php';

class SessionController {

    private PDO $db;
    private BillingController $billing;

    public function __construct() {
        $this->db      = Database::getConnection();
        $this->billing = new BillingController();
    }

    // =========================================================
    //  ENTRY
    // =========================================================

    /**
     * Process a vehicle entry.
     *
     * @param  int    $slotId       The target parking slot ID
     * @param  string $plateNumber  Vehicle plate (e.g. "ABC 1234")
     * @param  string $vehicleType  motorcycle | car | van | truck
     * @param  int    $adminId      ID of the staff processing this
     * @return array  ['success', 'reference_id', 'slot_code', 'entry_time', 'message']
     */
    public function processEntry(int $slotId, string $plateNumber, string $vehicleType, int $adminId): array {
        $plate = strtoupper(trim($plateNumber));

        // --- Validation ---
        if (!$plate || strlen($plate) < 4) {
            return $this->fail('Invalid plate number.');
        }
        $validTypes = ['motorcycle', 'car', 'van', 'truck'];
        if (!in_array($vehicleType, $validTypes, true)) {
            return $this->fail('Invalid vehicle type.');
        }

        // --- Security Check (Blacklist) ---
        $blacklist = ['AAA-0000', 'CRIMINAL-1', 'BANNED-99', 'CAR-666'];
        if (in_array($plate, $blacklist, true)) {
            return $this->fail("SECURITY BLOCK: Plate {$plate} is on the system blacklist. Access denied.");
        }

        // --- Check slot availability (lock row to prevent race condition) ---
        $slotStmt = $this->db->prepare("
            SELECT id, slot_code, status FROM slots WHERE id = :id FOR UPDATE
        ");

        try {
            $this->db->beginTransaction();
            $slotStmt->execute([':id' => $slotId]);
            $slot = $slotStmt->fetch();

            if (!$slot) {
                $this->db->rollBack();
                return $this->fail('Slot not found.');
            }
            if ($slot['status'] !== STATUS_AVAILABLE) {
                $this->db->rollBack();
                return $this->fail("Slot {$slot['slot_code']} is no longer available.");
            }

            // --- Check no duplicate active session for this plate ---
            $dupStmt = $this->db->prepare("
                SELECT id FROM sessions WHERE plate_number = :plate AND status = 'active' LIMIT 1
            ");
            $dupStmt->execute([':plate' => $plate]);
            if ($dupStmt->fetch()) {
                $this->db->rollBack();
                return $this->fail("Plate {$plate} already has an active session.");
            }

            // --- Create session record ---
            $refId = generateRefId();
            $now   = date('Y-m-d H:i:s');

            $insertSession = $this->db->prepare("
                INSERT INTO sessions
                    (reference_id, slot_id, plate_number, vehicle_type, entry_time, status, processed_by)
                VALUES
                    (:ref, :slot, :plate, :vtype, :entry, 'active', :admin)
            ");
            $insertSession->execute([
                ':ref'   => $refId,
                ':slot'  => $slotId,
                ':plate' => $plate,
                ':vtype' => $vehicleType,
                ':entry' => $now,
                ':admin' => $adminId,
            ]);
            $sessionId = (int) $this->db->lastInsertId();

            // --- Log Audit ---
            auditLog($this->db, $adminId, 'VEHICLE_ENTRY', 'sessions', $sessionId, [
                'plate' => $plate,
                'slot'  => $slot['slot_code'],
                'ref'   => $refId
            ]);

            // --- Update slot to Occupied ---
            $this->db->prepare("UPDATE slots SET status = 'occupied' WHERE id = :id")
                     ->execute([':id' => $slotId]);

            $this->db->commit();

            auditLog($this->db, $adminId, 'ENTRY_PROCESSED', 'sessions', $sessionId, [
                'plate'    => $plate,
                'slot'     => $slot['slot_code'],
                'ref'      => $refId,
            ]);

            return [
                'success'      => true,
                'reference_id' => $refId,
                'session_id'   => $sessionId,
                'slot_code'    => $slot['slot_code'],
                'plate'        => $plate,
                'vehicle_type' => $vehicleType,
                'entry_time'   => $now,
                'message'      => "Entry recorded! Reference: {$refId}",
            ];

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('[SessionController::processEntry] ' . $e->getMessage());
            return $this->fail('Server error during entry. Please retry.');
        }
    }

    // =========================================================
    //  EXIT
    // =========================================================

    /**
     * Process a vehicle exit — calculate fee, record transaction, free slot.
     *
     * @param  string $identifier   Reference ID OR plate number
     * @param  string $paymentMethod cash | gcash | card | maya
     * @param  int    $adminId
     * @param  bool   $isDiscounted
     * @return array  Full billing breakdown + receipt number
     */
    public function processExit(string $identifier, int $adminId, string $paymentMethod = 'cash', bool $isDiscounted = false): array {
        $identifier = strtoupper(trim($identifier));

        // --- Find active session ---
        $stmt = $this->db->prepare("
            SELECT s.*, sl.slot_code, sl.slot_type, sl.id AS slot_db_id
            FROM   sessions s
            JOIN   slots sl ON s.slot_id = sl.id
            WHERE  (s.reference_id = :ref OR s.plate_number = :plate)
              AND  s.status = 'active'
            LIMIT  1
        ");
        $stmt->execute([':ref' => $identifier, ':plate' => $identifier]);
        $session = $stmt->fetch();

        if (!$session) {
            return $this->fail("No active session found for: {$identifier}");
        }

        // --- Calculate fee ---
        $entryTime = new DateTime($session['entry_time']);
        $exitTime  = new DateTime();

        try {
            $fee = $this->billing->calculateFee($session['vehicle_type'], $entryTime, $exitTime, $isDiscounted, $session['slot_type']);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }

        $durationMins = $fee['duration_mins'];
        $exitTimeStr  = $exitTime->format('Y-m-d H:i:s');

        try {
            $this->db->beginTransaction();

            // Update session → completed
            $this->db->prepare("
                UPDATE sessions
                SET exit_time = :exit, duration_mins = :dur, status = 'completed', processed_by = :admin
                WHERE id = :id
            ")->execute([
                ':exit'  => $exitTimeStr,
                ':dur'   => $durationMins,
                ':admin' => $adminId,
                ':id'    => $session['id'],
            ]);

            // Record transaction
            $receiptNo = $this->billing->recordTransaction($session['id'], $fee, $paymentMethod, (int)$adminId);

            // Free up slot
            $this->db->prepare("UPDATE slots SET status = 'available' WHERE id = :id")
                ->execute([':id' => $session['slot_db_id']]);

            // --- Log Audit ---
            auditLog($this->db, $adminId, 'VEHICLE_EXIT', 'sessions', $session['id'], [
                'plate' => $session['plate_number'],
                'fee'   => $fee['total_fee'],
                'ref'   => $session['reference_id']
            ]);

            $this->db->commit();

            auditLog($this->db, $adminId, 'EXIT_PROCESSED', 'sessions', $session['id'], [
                'plate'      => $session['plate_number'],
                'slot'       => $session['slot_code'],
                'fee'        => $fee['total_fee'],
                'receipt_no' => $receiptNo,
            ]);

            return [
                'success'        => true,
                'receipt_no'     => $receiptNo,
                'reference_id'   => $session['reference_id'],
                'slot_code'      => $session['slot_code'],
                'plate'          => $session['plate_number'],
                'vehicle_type'   => $session['vehicle_type'],
                'entry_time'     => $session['entry_time'],
                'exit_time'      => $exitTimeStr,
                'duration_mins'  => $durationMins,
                'duration_label' => formatDuration($durationMins),
                'base_fee'       => $fee['base_fee'],
                'excess_fee'     => $fee['excess_fee'],
                'total_fee'      => $fee['total_fee'],
                'payment_method' => $paymentMethod,
                'note'           => $fee['note'],
                'message'        => "Exit processed! Total: " . peso($fee['total_fee']),
            ];

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('[SessionController::processExit] ' . $e->getMessage());
            return $this->fail('Server error during exit. Please retry.');
        }
    }

    // =========================================================
    //  QUERY HELPERS
    // =========================================================

    /**
     * Get all active sessions (for live dashboard).
     */
    public function getActiveSessions(): array {
        $stmt = $this->db->query("
            SELECT s.reference_id, s.plate_number, s.vehicle_type, s.entry_time,
                   TIMESTAMPDIFF(MINUTE, s.entry_time, NOW()) AS duration_mins,
                   sl.slot_code, z.name AS zone_name
            FROM   sessions s
            JOIN   slots sl ON s.slot_id = sl.id
            JOIN   zones z  ON sl.zone_id = z.id
            WHERE  s.status = 'active'
            ORDER  BY s.entry_time DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Get session by plate number for customer portal.
     */
    public function getByPlate(string $plate): ?array {
        $stmt = $this->db->prepare("
            SELECT s.*, sl.slot_code, z.name AS zone_name
            FROM   sessions s
            JOIN   slots sl ON s.slot_id = sl.id
            JOIN   zones z  ON sl.zone_id = z.id
            WHERE  s.plate_number = :plate AND s.status = 'active'
            LIMIT  1
        ");
        $stmt->execute([':plate' => strtoupper(trim($plate))]);
        return $stmt->fetch() ?: null;
    }

    // =========================================================
    //  PRIVATE
    // =========================================================

    private function fail(string $msg): array {
        return ['success' => false, 'message' => $msg];
    }
}