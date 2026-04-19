<?php
// index.php — Professional Premium Landing Page
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
<title>ParkSys Pro — Smart Parking Ecosystem</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>
<style>
:root {
  --primary: #3b82f6;
  --primary-dark: #2563eb;
  --accent: #f59e0b;
  --bg: #0f172a;
  --surface: rgba(30, 41, 59, 0.7);
  --text-main: #f8fafc;
  --text-muted: #94a3b8;
  --border: rgba(255, 255, 255, 0.1);
}

* { box-sizing: border-box; margin: 0; padding: 0; }

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-15px); }
}

/* ── Hero Section ── */
.hero-section {
  position: relative;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: #0f172a url('assets/img/hero_bg.png') center/cover no-repeat;
}

.hero-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, rgba(15, 23, 42, 0.8) 0%, rgba(15, 23, 42, 0.4) 50%, rgba(15, 23, 42, 0.9) 100%);
  z-index: 1;
}

/* ── Navigation ── */
.navbar {
  position: relative;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 32px 60px;
  max-width: 1400px;
  margin: 0 auto;
  width: 100%;
}

.brand { display: flex; align-items: center; gap: 14px; text-decoration: none; color: inherit; }
.brand-icon { 
  width: 44px; height: 44px; 
  background: linear-gradient(135deg, var(--primary), var(--primary-dark)); 
  border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff;
  box-shadow: 0 8px 16px rgba(37, 99, 235, 0.3);
}
.brand-name { font-size: 24px; font-weight: 800; letter-spacing: -0.02em; }

.nav-links { display: flex; gap: 40px; }
.nav-links a { text-decoration: none; color: var(--text-muted); font-size: 15px; font-weight: 500; transition: 0.3s; }
.nav-links a:hover { color: #fff; }

.login-btn {
  padding: 10px 24px;
  background: rgba(255,255,255,0.05);
  border: 1px solid var(--border);
  border-radius: 10px;
  color: #fff;
  text-decoration: none;
  font-weight: 600;
  font-size: 14px;
  backdrop-filter: blur(10px);
  transition: 0.3s;
}
.login-btn:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.3); }

/* ── Hero Content ── */
.hero-content {
  position: relative;
  z-index: 10;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
  padding: 0 40px;
  max-width: 1000px;
  margin: 0 auto;
}

.hero-badge {
  background: rgba(59, 130, 246, 0.1);
  color: var(--primary);
  padding: 8px 20px;
  border-radius: 100px;
  font-size: 13px;
  font-weight: 700;
  border: 1px solid rgba(59, 130, 246, 0.2);
  margin-bottom: 32px;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  animation: fadeInDown 0.8s ease-out;
}

.hero-content h1 {
  font-size: clamp(40px, 8vw, 84px);
  font-weight: 800;
  line-height: 1.1;
  letter-spacing: -0.04em;
  margin-bottom: 24px;
  background: linear-gradient(to right, #fff, #94a3b8);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  animation: fadeInUp 1s ease-out;
}

.hero-content p {
  font-size: clamp(16px, 2vw, 20px);
  color: var(--text-muted);
  max-width: 600px;
  margin-bottom: 48px;
  animation: fadeInUp 1.2s ease-out;
}

.cta-group {
  display: flex;
  gap: 20px;
  animation: fadeInUp 1.4s ease-out;
}

.btn {
  padding: 18px 40px;
  border-radius: 14px;
  font-size: 16px;
  font-weight: 700;
  text-decoration: none;
  transition: 0.3s;
  display: inline-flex;
  align-items: center;
  gap: 10px;
}

.btn-primary {
  background: var(--primary);
  color: #fff;
  box-shadow: 0 10px 30px rgba(59, 130, 246, 0.4);
}
.btn-primary:hover {
  background: var(--primary-dark);
  transform: translateY(-3px);
  box-shadow: 0 15px 40px rgba(59, 130, 246, 0.5);
}

.btn-secondary {
  background: rgba(255,255,255,0.03);
  color: #fff;
  border: 1px solid var(--border);
  backdrop-filter: blur(10px);
}
.btn-secondary:hover {
  background: rgba(255,255,255,0.08);
  border-color: rgba(255,255,255,0.2);
  transform: translateY(-3px);
}

/* ── Live Stats Ticker ── */
.stats-ticker {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 10;
  padding: 40px 60px;
  background: linear-gradient(to top, rgba(15,23,42,1), transparent);
  display: flex;
  justify-content: center;
  gap: 60px;
}

.ticker-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.ticker-label {
  font-size: 11px;
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.1em;
}

.ticker-val {
  font-size: 24px;
  font-weight: 800;
}

.status-dot {
  width: 8px;
  height: 8px;
  background: #10b981;
  border-radius: 50%;
  display: inline-block;
  margin-right: 8px;
  box-shadow: 0 0 10px #10b981;
  animation: pulse 2s infinite;
}

/* ── Features ── */
.features-section {
  padding: 120px 60px;
  max-width: 1400px;
  margin: 0 auto;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 32px;
}

.feature-card {
  background: var(--surface);
  border: 1px solid var(--border);
  padding: 40px;
  border-radius: 24px;
  transition: 0.4s;
}

.feature-card:hover {
  transform: translateY(-10px);
  border-color: rgba(59, 130, 246, 0.4);
  background: rgba(30, 41, 59, 0.9);
}

.feature-icon {
  width: 56px;
  height: 56px;
  background: rgba(59, 130, 246, 0.1);
  color: var(--primary);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 24px;
}

.feature-card h3 {
  font-size: 22px;
  font-weight: 700;
  margin-bottom: 16px;
}

.feature-card p {
  color: var(--text-muted);
  font-size: 16px;
  line-height: 1.7;
}

/* ── Animations ── */
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}
@keyframes fadeInDown {
  from { opacity: 0; transform: translateY(-30px); }
  to { opacity: 1; transform: translateY(0); }
}
@keyframes pulse {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

@media (max-width: 1024px) {
  .features-grid { grid-template-columns: 1fr 1fr; }
}

@media (max-width: 768px) {
  .navbar { padding: 24px 30px; }
  .nav-links { display: none; }
  .features-grid { grid-template-columns: 1fr; }
  .stats-ticker { flex-direction: column; gap: 24px; padding: 40px 30px; text-align: center; }
  .ticker-item { align-items: center; }
}
body {
  font-family: 'Outfit', sans-serif;
  background-color: var(--bg);
  color: var(--text-main);
  line-height: 1.6;
  overflow-x: hidden;
}

</style>
</head>
<body>

<div class="hero-section">
  <div class="hero-overlay"></div>
  
  <nav class="navbar">
    <a href="#" class="brand">
      <div class="brand-icon"><i data-lucide="parking-circle"></i></div>
      <div class="brand-name">ParkSys Pro</div>
    </a>
    <div class="nav-links">
      <a href="#features">Intelligence</a>
      <a href="#features">Ecosystem</a>
      <a href="views/customer/check_bill.php">Public Inquiry</a>
    </div>
    <a href="views/auth/login.php" class="login-btn">Management Access</a>
  </nav>

  <div class="hero-content">
    <div class="hero-badge">
      <i data-lucide="brain" style="width:14px; vertical-align: middle;"></i> AI-Powered Mobility Solution
    </div>
    <h1>The future of parking is here.</h1>
    <p>Experience an integrated ecosystem of automated license recognition, digital wallets, and real-time facility intelligence.</p>
    
    <div class="cta-group">
      <a href="views/customer/check_bill.php" class="btn btn-primary">
        Check My Bill <i data-lucide="arrow-right" style="width:18px"></i>
      </a>
      <a href="views/auth/login.php" class="btn btn-secondary">
        Admin Gateway
      </a>
    </div>
  </div>

  <div class="stats-ticker">
    <div class="ticker-item">
      <div class="ticker-label">Live Availability</div>
      <div class="ticker-val"><?= $stats['available'] ?? 0 ?> <span style="font-size: 14px; color: var(--text-muted); font-weight: 500;">/ <?= $stats['total'] ?? 0 ?> slots</span></div>
    </div>
    <div class="ticker-item">
      <div class="ticker-label">System Integrity</div>
      <div class="ticker-val"><span class="status-dot"></span>Operational</div>
    </div>
    <div class="ticker-item">
      <div class="ticker-label">Active Users</div>
      <div class="ticker-val">2.4k+</div>
    </div>
  </div>
</div>

<section class="features-section" id="features">
  <div class="features-grid">
    <div class="feature-card">
      <div class="feature-icon"><i data-lucide="scan"></i></div>
      <h3>AI Vision</h3>
      <p>Instant license plate recognition with automated risk assessment and VIP detection technology.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon"><i data-lucide="wallet"></i></div>
      <h3>Digital Wallet</h3>
      <p>Seamless cashless payments with real-time balance tracking and automated loyalty rewards.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon"><i data-lucide="bar-chart-3"></i></div>
      <h3>Live Intelligence</h3>
      <p>High-fidelity analytics dashboard providing forensic-level insights and revenue forecasting.</p>
    </div>
  </div>
</section>

<footer style="padding: 60px 40px; text-align: center; border-top: 1px solid var(--border); background: #0f172a;">
  <div style="margin-bottom: 24px;">
    <div class="brand-icon" style="margin: 0 auto 16px; width: 32px; height: 32px;"><i data-lucide="parking-circle" style="width:18px"></i></div>
    <div style="font-size: 18px; font-weight: 800;">ParkSys Pro</div>
  </div>
  <p style="font-size: 13px; color: var(--text-muted);">&copy; <?= date('Y') ?> Advanced Parking Solutions. Crafted for Enterprise Excellence.</p>
</footer>

<script>lucide.createIcons();</script>
</body>
</html>
