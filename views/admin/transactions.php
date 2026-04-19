<?php
// views/admin/transactions.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_ADMIN, ROLE_SUPERADMIN);

$db = Database::getConnection();

$limit = 15;
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Total count for pagination
$totalCount = $db->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
$totalPages = ceil($totalCount / $limit);

$stmt = $db->prepare("
    SELECT t.*, s.plate_number, s.vehicle_type, s.reference_id, sl.slot_code, z.name AS zone_name
    FROM transactions t
    JOIN sessions s ON t.session_id = s.id
    JOIN slots sl ON s.slot_id = sl.id
    JOIN zones z ON sl.zone_id = z.id
    ORDER BY t.paid_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$transactions = $stmt->fetchAll();

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
          <th>Discount</th>
          <th>Payment</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($transactions as $t): ?>
        <tr>
          <td>
            <div style="font-weight: 500; color: var(--text-main);"><?= date('M d, Y', strtotime($t['paid_at'])) ?></div>
            <div style="font-size: 11px; color: var(--text-muted);"><?= date('h:i A', strtotime($t['paid_at'])) ?></div>
          </td>
          <td><span class="mono" style="font-weight: 700; color: var(--primary); letter-spacing: 0.5px;"><?= $t['plate_number'] ?></span></td>
          <td>
            <div class="mono" style="font-size: 11px; font-weight: 600;"><?= $t['receipt_no'] ?></div>
            <div class="mono" style="font-size: 10px; color: var(--text-muted);"><?= $t['reference_id'] ?></div>
          </td>
          <td>
            <div style="font-weight: 600;"><?= $t['slot_code'] ?></div>
            <div style="font-size: 11px; color: var(--text-muted);"><?= $t['zone_name'] ?></div>
          </td>
          <td style="font-weight: 800; color: var(--text-main);"><?= peso($t['total_fee']) ?></td>
          <td>
            <?php if ($t['discount'] > 0): ?>
              <div style="color: var(--danger); font-size: 11px; font-weight: 700;">-<?= peso($t['discount']) ?></div>
              <div style="font-size: 9px; font-weight: 800; text-transform: uppercase; color: #dc2626; background: #fef2f2; padding: 1px 6px; border-radius: 4px; display: inline-block; margin-top: 2px;">PWD/Senior</div>
            <?php else: ?>
              <span style="color: var(--text-muted); font-size: 11px;">—</span>
            <?php endif; ?>
          </td>
          <td>
            <span style="font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 4px 10px; border-radius: 20px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;">
              <?= $t['payment_method'] ?>
            </span>
          </td>
          <td>
            <a href="../../api/get_receipt.php?receipt_no=<?= $t['receipt_no'] ?>" target="_blank" class="btn btn-secondary" style="padding: 6px 10px; font-size: 11px; font-weight: 700;">
              <i data-lucide="printer" style="width:12px; margin-right:4px; vertical-align:middle;"></i> Reprint
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

  <!-- Pagination UI -->
  <?php if ($totalPages > 1): ?>
  <div style="margin-top: 24px; display: flex; align-items: center; justify-content: space-between; border-top: 1px solid var(--border); padding-top: 20px;">
    <div style="font-size: 13px; color: var(--text-muted);">
      Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $totalCount) ?> of <?= $totalCount ?> records
    </div>
    <div style="display: flex; gap: 4px;">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>" class="btn" style="padding: 8px 12px; background: #fff; border: 1px solid var(--border); color: var(--text-main);"><i data-lucide="chevron-left" style="width: 16px;"></i></a>
      <?php endif; ?>

      <?php
      // Simple range logic for page numbers
      $start = max(1, $page - 2);
      $end   = min($totalPages, $page + 2);
      for ($i = $start; $i <= $end; $i++):
        $isActive = ($i === $page);
      ?>
        <a href="?page=<?= $i ?>" class="btn" style="padding: 8px 14px; background: <?= $isActive ? 'var(--primary)' : '#fff' ?>; border: 1px solid <?= $isActive ? 'var(--primary)' : 'var(--border)' ?>; color: <?= $isActive ? '#fff' : 'var(--text-main)' ?>; font-weight: 600;">
          <?= $i ?>
        </a>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page + 1 ?>" class="btn" style="padding: 8px 12px; background: #fff; border: 1px solid var(--border); color: var(--text-main);"><i data-lucide="chevron-right" style="width: 16px;"></i></a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
