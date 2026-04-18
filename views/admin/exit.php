<?php
// views/admin/exit.php — Advanced Real-time Exit Processing
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_ADMIN, ROLE_SUPERADMIN);

$db = Database::getConnection();
$plate = $_GET['plate'] ?? '';

$session = null;
if ($plate) {
    $stmt = $db->prepare("
        SELECT sess.*, s.slot_code, r.first_hour_fee, r.excess_hour_fee, r.grace_minutes
        FROM sessions sess
        JOIN slots s ON sess.slot_id = s.id
        JOIN rates r ON sess.vehicle_type = r.vehicle_type AND r.is_current = 1
        WHERE sess.plate_number = :plate AND sess.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([':plate' => $plate]);
    $session = $stmt->fetch();
}

$pageTitle = 'Process Exit';
ob_start();
?>

<div style="max-width: 900px; margin: 0 auto;">
    <div style="margin-bottom: 32px;">
        <h2 class="section-title">Automated Billing Checkout</h2>
        <p style="font-size: 13px; color: var(--text-muted);">Real-time fee calculation and transaction finalization.</p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 32px; align-items: start;">
        <!-- Left: Search/Identity -->
        <div class="card">
            <form method="GET" action="">
                <div class="form-group">
                    <label class="label">Locate Vehicle Plate</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" name="plate" class="input mono" value="<?= htmlspecialchars($plate) ?>" placeholder="ABC-1234" style="text-transform: uppercase;">
                        <button type="submit" class="btn btn-primary" style="padding: 10px;"><i data-lucide="search"></i></button>
                    </div>
                </div>
            </form>

            <?php if ($session): ?>
                <div style="margin-top: 24px; padding: 20px; background: var(--surface); border: 1px dashed var(--border); border-radius: 8px;">
                    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 12px;">SESSION IDENTITY</div>
                    <div style="display: grid; gap: 8px; font-size: 13px;">
                        <div style="display: flex; justify-content: space-between;"><span>Reference:</span> <span class="mono"><?= $session['reference_id'] ?></span></div>
                        <div style="display: flex; justify-content: space-between;"><span>Vehicle:</span> <strong><?= ucfirst($session['vehicle_type']) ?></strong></div>
                        <div style="display: flex; justify-content: space-between;"><span>Position:</span> <strong><?= $session['slot_code'] ?></strong></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right: Real-time Billing Ticker -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <?php if ($session): ?>
                <div style="background: var(--primary); color: #fff; padding: 32px; text-align: center;">
                    <div style="font-size: 14px; font-weight: 500; opacity: 0.8; margin-bottom: 8px;">TOTAL PAYABLE AMOUNT</div>
                    <div style="font-size: 56px; font-weight: 800; font-family: var(--font-mono); letter-spacing: -0.02em;" id="live-bill">₱0.00</div>
                </div>
                
                <div style="padding: 32px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
                        <div>
                            <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">Entry Timestamp</div>
                            <div style="font-weight: 600; font-size: 15px;" id="entry-time-str"><?= date('M d, Y H:i:s', strtotime($session['entry_time'])) ?></div>
                        </div>
                        <div>
                            <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">Total Duration</div>
                            <div style="font-weight: 600; font-size: 15px;" id="live-duration">--</div>
                        </div>
                    </div>

                    <div style="background: var(--surface); padding: 16px; border-radius: 8px; margin-bottom: 32px; border: 1px solid var(--border);">
                        <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">FEE BREAKDOWN</div>
                        <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 4px;">
                            <span>Base Fee (1st hr)</span>
                            <span id="base-fee-str"><?= peso($session['first_hour_fee']) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 14px;">
                            <span>Excess Hours</span>
                            <span id="excess-fee-str">₱0.00</span>
                        </div>
                    </div>

                    <form id="exit-form">
                        <input type="hidden" id="sess_id" value="<?= $session['id'] ?>">
                        <div class="form-group">
                            <label class="label">Payment Method</label>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                                <label style="cursor:pointer; border:1px solid var(--border); padding:10px; border-radius:6px; text-align:center;">
                                    <input type="radio" name="payment" value="cash" checked> <br> <span style="font-size:12px; font-weight:600;">Cash</span>
                                </label>
                                <label style="cursor:pointer; border:1px solid var(--border); padding:10px; border-radius:6px; text-align:center;">
                                    <input type="radio" name="payment" value="gcash"> <br> <span style="font-size:12px; font-weight:600;">GCash</span>
                                </label>
                                <label style="cursor:pointer; border:1px solid var(--border); padding:10px; border-radius:6px; text-align:center;">
                                    <input type="radio" name="payment" value="maya"> <br> <span style="font-size:12px; font-weight:600;">Maya</span>
                                </label>
                                <label style="cursor:pointer; border:1px solid var(--border); padding:10px; border-radius:6px; text-align:center;">
                                    <input type="radio" name="payment" value="card"> <br> <span style="font-size:12px; font-weight:600;">Card</span>
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 18px; font-size: 16px; margin-top: 16px;" id="submit-btn">
                            Complete Transaction & Open Gate
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div style="padding: 60px 40px; text-align: center; color: var(--text-muted);">
                    <i data-lucide="scan" style="width:48px; height:48px; opacity:0.2; margin-bottom:16px;"></i>
                    <p>Enter a plate number to initialize billing.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
<?php if ($session): ?>
    const entryTime = new Date("<?= $session['entry_time'] ?>").getTime();
    const firstHourFee = <?= $session['first_hour_fee'] ?>;
    const excessHourFee = <?= $session['excess_hour_fee'] ?>;
    const graceMins = <?= $session['grace_minutes'] ?>;

    function updateLiveBill() {
        const now = new Date().getTime();
        const diffSecs = Math.floor((now - entryTime) / 1000);
        const diffMins = Math.floor(diffSecs / 60);
        const diffHours = Math.floor(diffMins / 60);

        // Calculate
        let total = firstHourFee;
        if (diffMins > 60) {
            const excessHours = Math.ceil((diffMins - 60) / 60);
            total += (excessHours * excessHourFee);
            document.getElementById('excess-fee-str').textContent = '₱' + (excessHours * excessHourFee).toFixed(2);
        }

        // Format duration
        let h = Math.floor(diffMins / 60);
        let m = diffMins % 60;
        let s = diffSecs % 60;
        
        document.getElementById('live-bill').textContent = '₱' + total.toFixed(2);
        document.getElementById('live-duration').textContent = `${h}h ${m}m ${s}s`;
    }

    setInterval(updateLiveBill, 1000);
    updateLiveBill();

    document.getElementById('exit-form').onsubmit = function(e) {
        e.preventDefault();
        const paymentMethod = document.querySelector('input[name="payment"]:checked').value;
        const total = document.getElementById('live-bill').textContent;

        if (paymentMethod === 'gcash' || paymentMethod === 'maya') {
            showPaymentModal(paymentMethod, total);
        } else {
            processFinalExit(paymentMethod);
        }
    };

    function showPaymentModal(method, amount) {
        const modal = document.getElementById('payment-modal');
        const modalTitle = document.getElementById('modal-title');
        const modalAmount = document.getElementById('modal-amount');
        const modalIcon = document.getElementById('modal-icon');
        
        modalTitle.textContent = method.toUpperCase() + ' PAYMENT';
        modalAmount.textContent = amount;
        modal.style.display = 'flex';
    }

    function closePaymentModal() {
        document.getElementById('payment-modal').style.display = 'none';
        document.getElementById('submit-btn').disabled = false;
        document.getElementById('submit-btn').textContent = 'Complete Transaction';
    }

    function processFinalExit(method) {
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.textContent = 'Processing...';

        const fd = new FormData();
        fd.append('identifier', "<?= $session['plate_number'] ?>");
        fd.append('payment_method', method);

        fetch('<?= BASE_URL ?>/api/process_exit.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    const receiptUrl = '<?= BASE_URL ?>/api/get_receipt.php?receipt_no=' + res.receipt_no;
                    window.open(receiptUrl, '_blank');
                    setTimeout(() => window.location.href = 'dashboard.php', 500);
                } else {
                    btn.disabled = false;
                    btn.textContent = 'Complete Transaction';
                    alert(res.message);
                }
            });
    }
<?php endif; ?>
</script>

<!-- Payment Modal -->
<div id="payment-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.8); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#fff; width:100%; max-width:400px; border-radius:24px; padding:32px; text-align:center; animation: slideIn 0.3s ease-out;">
        <div id="modal-icon" style="margin-bottom:20px;">
            <i data-lucide="qr-code" style="width:64px; height:64px; color:var(--primary);"></i>
        </div>
        <h2 id="modal-title" style="font-size:20px; font-weight:800; margin-bottom:8px;">DIGITAL PAYMENT</h2>
        <p style="font-size:13px; color:var(--text-muted); margin-bottom:24px;">Please ask the customer to scan the QR code to settle the amount.</p>
        
        <div style="background:#f8fafc; padding:24px; border-radius:16px; margin-bottom:24px; border:2px dashed var(--border);">
            <div style="font-size:12px; font-weight:700; color:var(--primary); margin-bottom:8px;">PAYABLE AMOUNT</div>
            <div id="modal-amount" style="font-size:32px; font-weight:800; color:var(--text-main);">₱0.00</div>
        </div>

        <div style="display:grid; gap:12px;">
            <button onclick="processFinalExit(document.querySelector('input[name=&quot;payment&quot;]:checked').value)" class="btn btn-primary" style="width:100%; padding:16px;">Confirm Payment Received</button>
            <button onclick="closePaymentModal()" style="background:none; border:none; color:var(--text-muted); font-size:13px; font-weight:600; cursor:pointer;">Cancel / Use Other Method</button>
        </div>
    </div>
</div>

<style>
@keyframes slideIn {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
