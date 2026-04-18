<?php
// index.php — Professional Minimalist Landing Page
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'includes/helpers.php';

$db = Database::getConnection();

// Get live counts for the landing page
$stats = $db->query("
    SELECT 
        SUM(status = 'available') as available,
        COUNT(*) as total
    FROM slots
")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ParkSys Pro — Smart Parking Management</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>
<style>
:root {
  --primary: #2563eb;
  --text: #0f172a;
  --muted: #64748b;
  --bg: #ffffff;
  --surface: #f8fafc;
  --border: #e2e8f0;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Inter', sans-serif;
  background-color: var(--bg);
  color: var(--text);
  line-height: 1.5;
}

/* ── Header ── */
.navbar {
  display: flex; align-items: center; justify-content: space-between;
  padding: 24px 40px;
  position: sticky; top: 0; background: rgba(255,255,255,0.8);
  backdrop-filter: blur(12px); border-bottom: 1px solid var(--border);
  z-index: 100;
}
.brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; }
.brand-icon { width: 36px; height: 36px; background: var(--primary); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #fff; }
.brand-name { font-size: 20px; font-weight: 700; letter-spacing: -0.02em; }

.nav-links { display: flex; gap: 32px; }
.nav-links a { text-decoration: none; color: var(--muted); font-size: 14px; font-weight: 500; transition: 0.2s; }
.nav-links a:hover { color: var(--primary); }

/* ── Hero ── */
.hero {
  padding: 120px 40px; text-align: center;
  max-width: 900px; margin: 0 auto;
}
.hero-tag {
  display: inline-flex; align-items: center; gap: 8px;
  background: var(--surface); padding: 8px 16px; border-radius: 30px;
  font-size: 13px; font-weight: 600; color: var(--primary);
  margin-bottom: 24px; border: 1px solid var(--border);
}
.hero h1 { font-size: 64px; font-weight: 800; letter-spacing: -0.04em; line-height: 1.1; margin-bottom: 24px; }
.hero p { font-size: 20px; color: var(--muted); margin-bottom: 48px; }

.cta-group { display: flex; gap: 16px; justify-content: center; }
.btn {
  padding: 16px 32px; border-radius: 8px; font-size: 16px; font-weight: 600;
  text-decoration: none; transition: 0.2s; cursor: pointer; border: none;
}
.btn-primary { background: var(--primary); color: #fff; }
.btn-primary:hover { background: #1d4ed8; transform: translateY(-2px); }
.btn-secondary { background: #fff; color: var(--text); border: 1px solid var(--border); }
.btn-secondary:hover { background: var(--surface); }

/* ── Live Stats ── */
.stats-wrap {
  background: var(--surface); padding: 80px 40px;
  border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);
}
.stats-container {
  max-width: 1200px; margin: 0 auto;
  display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 40px;
}
.stat-card { text-align: left; }
.stat-label { font-size: 14px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 12px; }
.stat-val { font-size: 48px; font-weight: 700; color: var(--text); letter-spacing: -0.02em; }

/* ── Features ── */
.features { padding: 120px 40px; max-width: 1200px; margin: 0 auto; }
.feature-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 48px; }
.feat-item i { color: var(--primary); margin-bottom: 20px; width: 32px; height: 32px; }
.feat-item h3 { font-size: 20px; font-weight: 700; margin-bottom: 12px; }
.feat-item p { color: var(--muted); font-size: 15px; }

/* ── Footer ── */
footer { padding: 60px 40px; border-top: 1px solid var(--border); text-align: center; font-size: 14px; color: var(--muted); }

@media (max-width: 768px) {
  .hero h1 { font-size: 40px; }
  .feature-grid { grid-template-columns: 1fr; }
  .navbar { padding: 20px; }
  .nav-links { display: none; }
}
</style>
</head>
<body>

<nav class="navbar">
  <a href="#" class="brand">
    <div class="brand-icon"><i data-lucide="parking-circle"></i></div>
    <div class="brand-name">ParkSys Pro</div>
  </a>
  <div class="nav-links">
    <a href="#features">Solutions</a>
    <a href="#status">Live Status</a>
    <a href="views/customer/check_bill.php">Inquiry</a>
  </div>
  <a href="views/auth/login.php" class="btn btn-secondary" style="padding: 10px 20px; font-size: 14px;">Management Login</a>
</nav>

<main>
  <section class="hero">
    <div class="hero-tag">
      <i data-lucide="sparkles" style="width:14px"></i> Smart Parking for Modern Cities
    </div>
    <h1>Effortless parking management.</h1>
    <p>A minimalist, integrated solution for real-time monitoring, automated billing, and professional parking operations.</p>
    <div class="cta-group">
      <a href="views/customer/check_bill.php" class="btn btn-primary">Check My Bill</a>
      <a href="views/auth/login.php" class="btn btn-secondary">Admin Dashboard</a>
    </div>
  </section>

  <section class="stats-wrap" id="status">
    <div class="stats-container">
      <div class="stat-card">
        <div class="stat-label">Live Availability</div>
        <div class="stat-val"><?= $stats['available'] ?? 0 ?> <span style="font-size: 20px; color: var(--muted); font-weight: 400;">/ <?= $stats['total'] ?? 0 ?> slots</span></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">System Status</div>
        <div class="stat-val" style="color: var(--primary);">Operational</div>
      </div>
    </div>
  </section>

  <section class="features" id="features">
    <div class="feature-grid">
      <div class="feat-item">
        <i data-lucide="monitor-play"></i>
        <h3>Live Monitoring</h3>
        <p>Real-time visual feedback of every parking slot in your facility with instant status updates.</p>
      </div>
      <div class="feat-item">
        <i data-lucide="receipt"></i>
        <h3>Automated Billing</h3>
        <p>Accurate fee calculation based on duration and vehicle category with professional receipts.</p>
      </div>
      <div class="feat-item">
        <i data-lucide="shield-check"></i>
        <h3>Enterprise Security</h3>
        <p>Role-based access control and detailed audit logs to ensure accountability and data integrity.</p>
      </div>
    </div>
  </section>
</main>

<footer>
  <p>&copy; <?= date('Y') ?> ParkSys Pro Management. Built for performance and reliability.</p>
</footer>

<script>lucide.createIcons();</script>
</body>
</html>
