<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security_headers.php';
require_once __DIR__ . '/../../includes/helpers.php';
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= $csrfToken ?>">
<title>Login — ParkSys Pro</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>
<style>
:root {
  --primary: #2563eb;
  --primary-hover: #1d4ed8;
  --bg: #f8fafc;
  --text-main: #0f172a;
  --text-muted: #64748b;
  --border: #e2e8f0;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'Inter', sans-serif;
  background-color: var(--bg);
  color: var(--text-main);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.login-card {
  width: 100%;
  max-width: 400px;
  background: #ffffff;
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 40px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.brand {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 32px;
  justify-content: center;
}
.brand-icon {
  color: var(--primary);
  display: flex;
  align-items: center;
}
.brand-name {
  font-size: 20px;
  font-weight: 700;
  color: var(--text-main);
  letter-spacing: -0.02em;
}

.header { text-align: center; margin-bottom: 32px; }
.header h1 { font-size: 24px; font-weight: 700; color: var(--text-main); margin-bottom: 8px; }
.header p { color: var(--text-muted); font-size: 14px; }

.form-group { margin-bottom: 20px; }
.label { display: block; font-size: 14px; font-weight: 500; color: var(--text-main); margin-bottom: 8px; }

.input {
  width: 100%;
  padding: 10px 14px;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  color: var(--text-main);
  font-size: 14px;
  outline: none;
  transition: all 0.2s;
}
.input:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.btn {
  width: 100%;
  padding: 12px;
  background: var(--primary);
  color: #fff;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: background 0.2s;
  margin-top: 12px;
}
.btn:hover { background: var(--primary-hover); }
.btn:disabled { opacity: 0.7; cursor: not-allowed; }

.alert {
  padding: 12px;
  border-radius: 8px;
  font-size: 13px;
  margin-bottom: 20px;
  display: none;
}
.alert-danger { background: #fef2f2; border: 1px solid #fee2e2; color: #b91c1c; }
.alert-success { background: #f0fdf4; border: 1px solid #dcfce7; color: #15803d; }
.show { display: block; }

.demo-section {
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid var(--border);
  text-align: center;
}
.demo-title { font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 12px; }
.demo-chips { display: flex; justify-content: center; gap: 8px; }
.demo-chip {
  background: #f1f5f9;
  border: 1px solid var(--border);
  padding: 4px 12px;
  border-radius: 6px;
  font-size: 12px;
  color: var(--text-main);
  cursor: pointer;
}
.demo-chip:hover { background: #e2e8f0; }
</style>
</head>
<body>

<div class="login-card">
  <div class="brand">
    <div class="brand-icon"><i data-lucide="parking-circle" style="width:28px; height:28px"></i></div>
    <div class="brand-name">ParkSys Pro</div>
  </div>

  <div style="text-align:center; margin-bottom:20px;">
    <a href="<?= BASE_URL ?>/index.php" style="color:var(--text-muted); text-decoration:none; font-size:12px; display:inline-flex; align-items:center; gap:4px; font-weight:600;">
      <i data-lucide="arrow-left" style="width:14px"></i> Back to Home
    </a>
  </div>

  <div class="header">
    <h1>Sign in</h1>
    <p>Please enter your credentials</p>
  </div>

  <div id="alert" class="alert"></div>

  <form id="login-form" onsubmit="event.preventDefault(); startLogin();">
    <div class="form-group">
      <label class="label">Email address</label>
      <input class="input" type="email" id="email" placeholder="admin@parksys.com" required>
    </div>

    <div class="form-group">
      <label class="label">Password</label>
      <input class="input" type="password" id="password" placeholder="••••••••" required>
    </div>

    <button type="submit" class="btn" id="login-btn">Sign in to Dashboard</button>
  </form>

  <div class="demo-section">
    <div class="demo-title">Access accounts</div>
    <div class="demo-chips">
      <div class="demo-chip" onclick="fill('admin@parksys.com','admin123')">Admin</div>
      <div class="demo-chip" onclick="fill('staff@parksys.com','staff123')">Staff</div>
    </div>
  </div>
</div>

<script>
lucide.createIcons();

function fill(e, p) {
  document.getElementById('email').value = e;
  document.getElementById('password').value = p;
}

function startLogin() {
  const email = document.getElementById('email').value;
  const pass  = document.getElementById('password').value;
  const btn   = document.getElementById('login-btn');
  const alert = document.getElementById('alert');
  
  btn.disabled = true;
  btn.textContent = 'Authenticating...';
  alert.className = 'alert';

  const fd = new FormData();
  fd.append('email', email);
  fd.append('password', pass);

  fetch('<?= BASE_URL ?>/api/auth_login.php', { 
    method: 'POST', 
    body: fd,
    headers: { 
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        alert.textContent = 'Success! Redirecting...';
        alert.className = 'alert alert-success show';
        setTimeout(() => window.location.href = res.redirect, 500);
      } else {
        btn.disabled = false;
        btn.textContent = 'Sign in to Dashboard';
        alert.textContent = res.message;
        alert.className = 'alert alert-danger show';
      }
    })
    .catch(err => {
      btn.disabled = false;
      btn.textContent = 'Sign in to Dashboard';
      alert.textContent = 'Connection error.';
      alert.className = 'alert alert-danger show';
    });
}
</script>
</body>
</html>