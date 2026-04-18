<?php
// views/admin/transactions.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_ADMIN, ROLE_SUPERADMIN);

$db = Database::getConnection();

$transactions = $db->query("
    SELECT t.*, s.plate_number, s.vehicle_type, s.reference_id, sl.slot_code, z.name AS zone_name
    FROM transactions t
    JOIN sessions s ON t.session_id = s.id
    JOIN slots sl ON s.slot_id = sl.id
    JOIN zones z ON sl.zone_id = z.id
    ORDER BY t.paid_at DESC
    LIMIT 100
")->fetchAll();

$pageTitle = 'Transaction History';
ob_start();
?>

<div class="card">
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Date & Time</th>
          <th>Plate Number</th>
          <th>Reference / Receipt</th>
          <th>Slot</th>
          <th>Total Amount</th>
          <th>Payment</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($transactions as $t): ?>
        <tr>
          <td>
            <div style="font-weight: 500;"><?= date('M d, Y', strtotime($t['paid_at'])) ?></div>
            <div style="font-size: 11px; color: var(--text-muted);"><?= date('h:i A', strtotime($t['paid_at'])) ?></div>
          </td>
          <td><span class="mono" style="font-weight: 600; letter-spacing: 0.5px;"><?= $t['plate_number'] ?></span></td>
          <td>
            <div class="mono" style="font-size: 11px;"><?= $t['receipt_no'] ?></div>
            <div class="mono" style="font-size: 10px; color: var(--text-muted);"><?= $t['reference_id'] ?></div>
          </td>
          <td><?= $t['slot_code'] ?> <span style="font-size:11px; color:var(--text-muted)">(<?= $t['zone_name'] ?>)</span></td>
          <td style="font-weight: 600; color: var(--text-main);"><?= peso($t['total_fee']) ?></td>
          <td>
            <span style="font-size: 10px; font-weight: 600; text-transform: uppercase; padding: 4px 8px; border-radius: 4px; background: var(--bg); border: 1px solid var(--border);">
              <?= $t['payment_method'] ?>
            </span>
          </td>
          <td>
            <a href="../../api/get_receipt.php?receipt_no=<?= $t['receipt_no'] ?>" target="_blank" class="btn btn-secondary" style="padding: 6px 10px; font-size: 11px;">
              Reprint
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($transactions)): ?>
        <tr>
          <td colspan="7" style="padding: 64px; text-align: center; color: var(--text-muted);">No records found.</td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
