<?php
// views/customer/check_bill.php — Premium Public Inquiry Portal
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../controllers/SessionController.php';
require_once __DIR__ . '/../../controllers/BillingController.php';

$sessionCtrl = new SessionController();
$billing     = new BillingController();

$result = null;
$error  = null;
$plate  = clean($_GET['plate'] ?? '');

if ($plate) {
    $session = $sessionCtrl->getByPlate($plate);
    if ($session) {
        $estimate = $billing->estimateBill($session['vehicle_type'], $session['entry_time']);
        $result   = array_merge($session, $estimate);
    } else {
        $error = "No active parking session found for plate: " . htmlspecialchars($plate);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inquiry — ParkSys Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --primary: #2563eb;
            --primary-glow: rgba(37, 99, 235, 0.4);
            --bg: #f8fafc;
            --glass: rgba(255, 255, 255, 0.8);
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .header {
            width: 100%;
            padding: 24px;
            display: flex;
            justify-content: center;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-main);
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            box-shadow: 0 4px 12px var(--primary-glow);
        }

        .brand-name {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
            margin-top: 20px;
        }

        .search-card {
            background: var(--glass);
            backdrop-filter: blur(12px);
            padding: 32px;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
        }

        .search-title {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 8px;
            text-align: center;
        }

        .search-subtitle {
            font-size: 14px;
            color: var(--text-muted);
            text-align: center;
            margin-bottom: 32px;
        }

        .input-group {
            position: relative;
            margin-bottom: 16px;
        }

        .input {
            width: 100%;
            padding: 18px 24px;
            border-radius: 16px;
            border: 2px solid #e2e8f0;
            font-size: 20px;
            font-weight: 700;
            text-transform: uppercase;
            text-align: center;
            outline: none;
            transition: 0.3s;
            background: #fff;
        }

        .input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-glow);
        }

        .btn-search {
            width: 100%;
            padding: 18px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
            box-shadow: 0 8px 20px var(--primary-glow);
        }

        .btn-search:active { transform: scale(0.98); }

        /* Result Styles */
        .result-card {
            background: var(--glass);
            backdrop-filter: blur(12px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .plate-banner {
            background: #0f172a;
            padding: 40px 20px;
            text-align: center;
            color: #fff;
        }

        .plate-text {
            font-size: 48px;
            font-weight: 900;
            letter-spacing: 0.1em;
            font-family: 'JetBrains Mono', monospace;
        }

        .vtype-badge {
            display: inline-block;
            padding: 6px 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            margin-top: 12px;
            text-transform: uppercase;
        }

        .info-grid {
            padding: 32px;
            display: grid;
            gap: 24px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-label {
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .info-value {
            font-size: 16px;
            font-weight: 700;
        }

        .ticker-card {
            background: #eff6ff;
            padding: 32px;
            border-radius: 20px;
            text-align: center;
            margin-top: 12px;
        }

        .ticker-label {
            font-size: 12px;
            font-weight: 800;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 12px;
        }

        .ticker-value {
            font-size: 56px;
            font-weight: 900;
            color: var(--primary);
            line-height: 1;
        }

        .slot-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #fff;
            padding: 12px 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            justify-content: center;
            margin-bottom: 24px;
        }

        .error {
            background: #fef2f2;
            color: #dc2626;
            padding: 20px;
            border-radius: 16px;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            border: 1px solid #fecaca;
        }

        .footer {
            margin-top: auto;
            padding: 40px;
            text-align: center;
            font-size: 12px;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

<header class="header">
    <a href="#" class="brand">
        <div class="brand-icon"><i data-lucide="parking-circle"></i></div>
        <div class="brand-name">ParkSys Pro</div>
    </a>
</header>

<div class="container">
    <div class="search-card">
        <h2 class="search-title">Bill Inquiry</h2>
        <p class="search-subtitle">Enter your plate number to view charges.</p>
        
        <form method="GET">
            <div class="input-group">
                <input type="text" name="plate" class="input" placeholder="ABC-1234" value="<?= htmlspecialchars($plate) ?>" required autofocus>
            </div>
            <button type="submit" class="btn-search">Calculate Bill</button>
        </form>
    </div>

    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($result): ?>
        <div class="result-card">
            <div class="plate-banner">
                <div class="plate-text"><?= htmlspecialchars($result['plate_number']) ?></div>
                <div class="vtype-badge"><?= $result['vehicle_type'] ?></div>
            </div>

            <div class="info-grid">
                <div class="slot-pill">
                    <i data-lucide="map-pin" style="width:16px; color: var(--primary);"></i>
                    <span style="font-weight: 700; font-size: 14px;"><?= $result['zone_name'] ?> — <?= $result['slot_code'] ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Check-in Time</span>
                    <span class="info-value"><?= date('h:i A, M d', strtotime($result['entry_time'])) ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Live Duration</span>
                    <span class="info-value" id="live-duration"><?= formatDuration($result['duration_mins']) ?></span>
                </div>

                <div class="ticker-card">
                    <div class="ticker-label">Current Estimated Bill</div>
                    <div class="ticker-value" id="live-total">₱<?= number_format($result['total_fee'], 2) ?></div>
                </div>

                <p style="font-size: 11px; color: var(--text-muted); text-align: center; line-height: 1.5; margin-top: 12px;">
                    * This is a live estimation. Final amount will be settled at the exit booth upon checkout.
                </p>
            </div>
        </div>

        <script>
            // Live Ticker Logic
            const entryTime = new DateTime("<?= $result['entry_time'] ?>");
            const baseFee = <?= $result['base_fee'] ?>;
            // Simplified ticker: we'll just refresh the display based on real-time elapsed
            setInterval(() => {
                // In a real production app, we'd call an API, but for UX, 
                // we'll simulate the ticking of time here.
                const now = new Date();
                const diffMs = now - new Date("<?= $result['entry_time'] ?>");
                const diffMins = Math.floor(diffMs / 60000);
                
                // Update Duration Display
                const hours = Math.floor(diffMins / 60);
                const mins = diffMins % 60;
                document.getElementById('live-duration').textContent = `${hours}h ${mins}m`;
                
                // For the total, we'll keep it as is unless it's been a minute.
                // Re-calculating complex parking rates in pure JS is risky, 
                // but for the ticker feel, it works.
            }, 60000);
        </script>
    <?php endif; ?>
</div>

<footer class="footer">
    Powered by ParkSys Enterprise v1.0.2<br>
    &copy; 2026 Smart Mobility Solutions
</footer>

<script>lucide.createIcons();</script>
</body>
</html>