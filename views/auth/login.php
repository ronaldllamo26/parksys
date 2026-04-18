<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — ParkSys Pro</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>
<style>
:root {
  --primary: #2563eb;
  --bg: #ffffff;
  --text: #0f172a;
  --muted: #64748b;
  --border: #e2e8f0;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Inter', sans-serif;
  background-color: var(--bg);
  color: var(--text);
  display: flex; align-items: center; justify-content: center;
  min-height: 100vh;
}
.login-container {
  width: 100%; max-width: 400px;
  padding: 20px;
}
.brand { display: flex; align-items: center; gap: 12px; margin-bottom: 48px; justify-content: center; }
.brand-icon { width: 36px; height: 36px; background: var(--primary); border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #fff; }
.brand-name { font-size: 20px; font-weight: 700; color: var(--text); letter-spacing: -0.02em; }

.header { margin-bottom: 32px; }
.header h1 { font-size: 24px; font-weight: 700; margin-bottom: 8px; letter-spacing: -0.02em; }
.header p { color: var(--muted); font-size: 14px; }

.form-group { margin-bottom: 24px; }
.label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; }
.input {
  width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1;
  border-radius: 6px; outline: none; transition: 0.2s; font-size: 14px;
}
.input:focus { border-color: var(--primary); border-width: 2px; padding: 11px 15px; }

.btn {
  width: 100%; padding: 14px; background: var(--primary); color: #fff;
  border: none; border-radius: 6px; font-weight: 600; font-size: 14px;
  cursor: pointer; transition: 0.2s;
}
.btn:hover { background: #1d4ed8; }

.alert {
  padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 24px;
  display: none; border: 1px solid transparent;
}
.alert-danger { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
.alert-success { background: #f0fdf4; border-color: #bbf7d0; color: #16a34a; }
.show { display: block; }

.footer { margin-top: 48px; padding-top: 24px; border-top: 1px solid var(--border); text-align: center; }
.footer p { font-size: 12px; color: var(--muted); }
.demo-btn { background: #f8fafc; border: 1px solid var(--border); padding: 6px 12px; border-radius: 4px; font-size: 11px; cursor: pointer; color: var(--text); margin: 0 4px; }
</style>
</head>
<body>

<div class="login-container">
  <div class="brand">
    <div class="brand-icon"><i data-lucide="parking-circle"></i></div>
    <div class="brand-name">ParkSys Pro</div>
  </div>

  <div class="header">
    <h1>Sign in</h1>
    <p>Enter your credentials to access the system</p>
  </div>

  <div id="alert" class="alert"></div>

  <form id="login-form">
    <div class="form-group">
      <label class="label">Email address</label>
      <input class="input" type="email" id="email" placeholder="admin@parksys.com">
    </div>

    <div class="form-group">
      <label class="label">Password</label>
      <input class="input" type="password" id="password" placeholder="••••••••">
    </div>

    <button type="button" class="btn" id="login-btn" onclick="startLogin()">Sign in to Dashboard</button>
  </form>

  <div class="footer">
    <p>Demo accounts</p>
    <div style="margin-top: 12px;">
      <button class="demo-btn" onclick="fill('admin@parksys.com','admin123')">Admin</button>
      <button class="demo-btn" onclick="fill('staff@parksys.com','staff123')">Staff</button>
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
  
  if(!email || !pass) {
    alert.textContent = 'Please fill in all fields.';
    alert.className = 'alert alert-danger show';
    return;
  }

  btn.disabled = true;
  btn.textContent = 'Authenticating...';
  alert.className = 'alert';

  const fd = new FormData();
  fd.append('email', email);
  fd.append('password', pass);

  fetch('<?= BASE_URL ?>/api/auth_login.php', { 
    method: 'POST', 
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
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
      alert.textContent = 'Network error or server unavailable.';
      alert.className = 'alert alert-danger show';
      console.error(err);
    });
}
</script>
</body>
</html>