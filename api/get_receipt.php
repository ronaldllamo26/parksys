<?php
// api/get_receipt.php — Official Thermal Receipt Generator
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/helpers.php';

$receiptNo = clean($_GET['receipt_no'] ?? '');
if (!$receiptNo) die("Invalid Receipt Number");

$db = Database::getConnection();

// Fetch full transaction details
$stmt = $db->prepare("
    SELECT t.*, s.plate_number, s.vehicle_type, s.entry_time, s.exit_time, s.duration_mins, 
           u.name as staff_name, sl.slot_code
    FROM transactions t
    JOIN sessions s ON t.session_id = s.id
    JOIN slots sl ON s.slot_id = sl.id
    LEFT JOIN users u ON t.handled_by = u.id
    WHERE t.receipt_no = :receipt
    LIMIT 1
");
$stmt->execute([':receipt' => $receiptNo]);
$data = $stmt->fetch();

if (!$data) die("Receipt not found");

// Fetch organization branding from settings
$orgName = getSetting($db, 'app_name', 'ParkSys Pro');
$orgAddr = getSetting($db, 'app_address', 'Parking Management System');
?>
<!DOCTYPE html>
<html>
<head>
    <title>OR #<?= $receiptNo ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; width: 80mm; margin: 0 auto; padding: 10px; color: #000; font-size: 12px; }
        .text-center { text-align: center; }
        .header { margin-bottom: 20px; border-bottom: 1px dashed #000; padding-bottom: 10px; }
        .brand { font-size: 16px; font-weight: 800; text-transform: uppercase; }
        .address { font-size: 10px; margin-top: 4px; }
        .row { display: flex; justify-content: space-between; margin: 4px 0; }
        .divider { border-top: 1px dashed #000; margin: 10px 0; }
        .total { font-size: 16px; font-weight: 800; }
        .footer { font-size: 10px; margin-top: 20px; border-top: 1px dashed #000; padding-top: 10px; }
        @media print { .no-print { display: none; } }
        .btn-print { width: 100%; padding: 10px; background: #000; color: #fff; border: none; cursor: pointer; margin-bottom: 20px; font-weight: 700; }
    </style>
</head>
<body onload="/* window.print() */">

    <button class="btn-print no-print" onclick="window.print()">PRINT RECEIPT</button>

    <div class="header text-center">
        <div class="brand"><?= htmlspecialchars($orgName) ?></div>
        <div class="address"><?= nl2br(htmlspecialchars($orgAddr)) ?></div>
    </div>

    <div class="text-center" style="margin-bottom: 15px;">
        <strong>OFFICIAL RECEIPT</strong><br>
        #<?= $data['receipt_no'] ?>
    </div>

    <div class="row"><span>Date:</span> <span><?= date('M d, Y h:i A', strtotime($data['paid_at'])) ?></span></div>
    <div class="row"><span>Staff:</span> <span><?= htmlspecialchars($data['staff_name']) ?></span></div>
    
    <div class="divider"></div>

    <div class="row"><span>Plate No:</span> <strong><?= htmlspecialchars($data['plate_number']) ?></strong></div>
    <div class="row"><span>Vehicle:</span> <span style="text-transform: capitalize;"><?= $data['vehicle_type'] ?></span></div>
    <div class="row"><span>Slot:</span> <span><?= $data['slot_code'] ?></span></div>

    <div class="divider"></div>

    <div class="row"><span>Check-in:</span> <span><?= date('h:i A', strtotime($data['entry_time'])) ?></span></div>
    <div class="row"><span>Check-out:</span> <span><?= date('h:i A', strtotime($data['exit_time'])) ?></span></div>
    <div class="row"><span>Duration:</span> <span><?= formatDuration($data['duration_mins']) ?></span></div>

    <div class="divider"></div>

    <div class="row"><span>Base Fee:</span> <span><?= peso($data['base_fee']) ?></span></div>
    <div class="row"><span>Excess Fee:</span> <span><?= peso($data['excess_fee']) ?></span></div>
    <div class="row" class="total"><span>TOTAL PAID:</span> <span class="total"><?= peso($data['total_fee']) ?></span></div>
    <div class="row"><span>Payment:</span> <span style="text-transform: uppercase;"><?= $data['payment_method'] ?></span></div>

    <div class="footer text-center">
        Thank you for parking with us!<br>
        Have a safe drive.<br><br>
        ParkSys Pro Enterprise v1.0.2
    </div>

</body>
</html>