<?php
// views/admin/print_remittance.php — Digital Handover Slip
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requireRole(ROLE_ADMIN, ROLE_SUPERADMIN);

$db = Database::getConnection();
$uid = $_SESSION[SESSION_USER_ID];

// Fetch same data as the API for consistency
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_txns,
        COALESCE(SUM(total_fee), 0) as total_revenue,
        COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN total_fee ELSE 0 END), 0) as cash_total,
        COALESCE(SUM(CASE WHEN payment_method = 'gcash' THEN total_fee ELSE 0 END), 0) as gcash_total
    FROM transactions t
    JOIN sessions s ON t.session_id = s.id
    WHERE s.processed_by = :uid AND DATE(t.paid_at) = CURDATE()
");
$stmt->execute([':uid' => $uid]);
$summary = $stmt->fetch();

$entries = $db->prepare("SELECT COUNT(*) FROM sessions WHERE processed_by = :uid AND DATE(entry_time) = CURDATE()");
$entries->execute([':uid' => $uid]);
$totalEntries = $entries->fetchColumn();

$refId = "SHIFT-" . date('Ymd') . "-" . $uid . "-" . rand(100, 999);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shift Remittance Slip - <?= $refId ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; padding: 40px; color: #000; line-height: 1.4; }
        .slip { max-width: 400px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        .header { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 15px; margin-bottom: 15px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 14px; }
        .total { font-size: 20px; font-weight: 800; border-top: 1px solid #000; margin-top: 15px; padding-top: 10px; }
        .qr-placeholder { background: #f0f0f0; width: 120px; height: 120px; margin: 20px auto; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #666; border: 1px solid #ccc; }
        @media print { .no-print { display: none; } body { padding: 0; } .slip { border: none; box-shadow: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor:pointer;">Print Remittance Slip</button>
        <button onclick="window.close()" style="padding: 10px 20px; cursor:pointer;">Close Window</button>
    </div>

    <div class="slip">
        <div class="header">
            <h2 style="margin:0;">ParkSys Pro</h2>
            <div style="font-size: 12px;">Automated Facility Management</div>
            <div style="font-size: 14px; margin-top: 10px; font-weight:800;">SHIFT REMITTANCE SLIP</div>
        </div>

        <div class="row"><span>Reference:</span> <span><?= $refId ?></span></div>
        <div class="row"><span>Staff Name:</span> <span><?= $_SESSION[SESSION_USER_NAME] ?></span></div>
        <div class="row"><span>Date:</span> <span><?= date('M d, Y') ?></span></div>
        <div class="row"><span>Shift End:</span> <span><?= date('H:i:s') ?></span></div>

        <div style="margin: 15px 0; border-top: 1px dashed #000; padding-top: 10px;">
            <div class="row"><span>Vehicles Entered:</span> <span><?= $totalEntries ?></span></div>
            <div class="row"><span>Vehicles Exited:</span> <span><?= $summary['total_txns'] ?></span></div>
        </div>

        <div style="margin: 15px 0; border-top: 1px dashed #000; padding-top: 10px;">
            <div class="row"><span>Cash Total:</span> <span><?= peso($summary['cash_total']) ?></span></div>
            <div class="row"><span>GCash Total:</span> <span><?= peso($summary['gcash_total']) ?></span></div>
        </div>

        <div class="row total">
            <span>TOTAL REMIT:</span>
            <span><?= peso($summary['total_revenue']) ?></span>
        </div>

        <div class="qr-placeholder">
            [ HANDOVER QR ]<br>SCAN TO VERIFY
        </div>

        <div style="text-align: center; font-size: 10px; margin-top: 20px; color: #666;">
            I hereby certify that the above collections are accurate and true.
            <div style="margin-top: 40px; border-top: 1px solid #000; width: 200px; margin-left: auto; margin-right: auto; padding-top: 5px;">
                Signature over Printed Name
            </div>
        </div>
    </div>

    <script>
        // Optional: auto-trigger print
        // window.print();
    </script>
</body>
</html>
