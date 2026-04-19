<?php
// controllers/BillingController.php — Fee Calculation & Transaction Recording

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

class BillingController {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Calculate parking fee.
     *
     * Logic:
     *   1. Within grace period → FREE
     *   2. First hour  → first_hour_fee (flat)
     *   3. Each additional hour (ceiling) → excess_hour_fee
     *   4. If total > flat_day_rate → cap at flat day rate
     *
     * @throws Exception if no rate config found
     */
    public function calculateFee(string $vehicleType, DateTime $entry, DateTime $exit, bool $isDiscounted = false, string $slotType = 'standard'): array {
        // Fetch active rate
        $targetType = ($slotType === 'vip') ? 'vip' : $vehicleType;

        $stmt = $this->db->prepare("
            SELECT * FROM rates
            WHERE vehicle_type = :type AND is_current = 1
            ORDER BY effective_from DESC
            LIMIT 1
        ");
        $stmt->execute([':type' => $targetType]);
        $rate = $stmt->fetch();

        if (!$rate) {
            throw new Exception("No active rate found for vehicle type: {$vehicleType}");
        }

        $mins         = durationMins($entry, $exit);
        $graceMins    = (int)   $rate['grace_minutes'];
        $firstHourFee = (float) $rate['first_hour_fee'];
        $excessFee    = (float) $rate['excess_hour_fee'];
        $flatDayRate  = $rate['flat_day_rate'] ? (float) $rate['flat_day_rate'] : null;

        // ── GRACE PERIOD ─────────────────────────────────────
        if ($mins <= $graceMins) {
            return [
                'duration_mins' => $mins,
                'base_fee'      => 0.00,
                'excess_fee'    => 0.00,
                'discount'      => 0.00,
                'total_fee'     => 0.00,
                'rate_id'       => (int) $rate['id'],
                'note'          => "Within {$graceMins}-min grace period — no charge",
            ];
        }

        // ── FIRST HOUR ────────────────────────────────────────
        $baseFee       = $firstHourFee;
        $totalExcessFee = 0.00;

        // ── EXCESS HOURS (ceiling: partial hour = full hour) ──
        if ($mins > 60) {
            $excessMins  = $mins - 60;
            $excessHours = (int) ceil($excessMins / 60);
            $totalExcessFee = round($excessHours * $excessFee, 2);
        }

        $subtotal = $baseFee + $totalExcessFee;

        // ── FLAT DAY RATE CAP ─────────────────────────────────
        $note = ($targetType === 'vip') ? 'VIP Premium Rate' : 'Standard rate';
        if ($flatDayRate !== null && $subtotal >= $flatDayRate) {
            $subtotal = $flatDayRate;
            $note     = 'Flat 24-hr rate applied (cheaper than hourly)';
        }

        // ── DISCOUNT LOGIC (PWD/Senior 20%) ───────────────────
        $discountAmount = 0.00;
        if ($isDiscounted) {
            $discountAmount = round($subtotal * 0.20, 2);
            $subtotal      -= $discountAmount;
            $note          .= " | 20% PWD/Senior Discount Applied (-₱$discountAmount)";
        }

        return [
            'duration_mins' => $mins,
            'base_fee'      => round($baseFee, 2),
            'excess_fee'    => round($totalExcessFee, 2),
            'discount'      => $discountAmount,
            'total_fee'     => round($subtotal, 2),
            'rate_id'       => (int) $rate['id'],
            'note'          => $note,
        ];
    }

    /**
     * Persist the billing record to `transactions`.
     */
    public function recordTransaction(int $sessionId, array $fee, string $paymentMethod, int $handledBy): string {
        $receiptNo = generateReceiptNo();

        $stmt = $this->db->prepare("
            INSERT INTO transactions
                (session_id, rate_id, base_fee, excess_fee, discount, total_fee, payment_method, receipt_no, handled_by)
            VALUES
                (:sid, :rid, :base, :excess, :discount, :total, :method, :receipt, :hby)
        ");
        $stmt->execute([
            ':sid'      => $sessionId,
            ':rid'      => $fee['rate_id'],
            ':base'     => $fee['base_fee'],
            ':excess'   => $fee['excess_fee'],
            ':discount' => $fee['discount'] ?? 0.00,
            ':total'    => $fee['total_fee'],
            ':method'   => $paymentMethod,
            ':receipt'  => $receiptNo,
            ':hby'      => $handledBy,
        ]);

        return $receiptNo;
    }

    /**
     * Get estimated bill for a CURRENTLY active session (no exit yet).
     * Used by customer "Check My Bill" portal.
     */
    public function estimateBill(string $vehicleType, string $entryTimeStr, string $slotType = 'standard'): array {
        $entry = new DateTime($entryTimeStr);
        $now   = new DateTime();
        return $this->calculateFee($vehicleType, $entry, $now, false, $slotType);
    }

    /**
     * Get today's revenue summary for dashboard.
     */
    public function getTodayRevenue(): array {
        $stmt = $this->db->query("
            SELECT
                COUNT(*)          AS total_transactions,
                SUM(total_fee)    AS total_revenue,
                AVG(total_fee)    AS avg_fee,
                MAX(total_fee)    AS max_fee
            FROM transactions t
            JOIN sessions s ON t.session_id = s.id
            WHERE DATE(t.paid_at) = CURDATE()
        ");
        return $stmt->fetch() ?: [];
    }

    /**
     * Get last 7 days daily revenue for chart.
     */
    public function getWeeklyRevenue(): array {
        $stmt = $this->db->query("
            SELECT
                DATE(t.paid_at) AS day,
                SUM(t.total_fee) AS revenue,
                COUNT(*)         AS count
            FROM transactions t
            WHERE t.paid_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY DATE(t.paid_at)
            ORDER BY day ASC
        ");
        return $stmt->fetchAll();
    }
}