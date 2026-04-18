<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="base-url" content="<?= BASE_URL ?>">
<title><?= $pageTitle ?? 'Dashboard' ?> — ParkSys Pro</title>
<link href="<?= BASE_URL ?>/assets/css/parksys.css" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<!-- ── Sidebar ── -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><i data-lucide="parking-circle"></i></div>
    <div class="brand-name">ParkSys Pro</div>
  </div>

  <nav class="nav-menu">
    <?php
    $role = $_SESSION[SESSION_USER_ROLE];
    $menu = [];

    if ($role === ROLE_SUPERADMIN) {
      $menu = [
        ['icon'=>'bar-chart-3',      'label'=>'Analytics Dashboard', 'href'=>'/views/superadmin/analytics.php'],
        ['icon'=>'monitor-play',     'label'=>'Live Slot Monitor',   'href'=>'/views/admin/dashboard.php'],
        ['icon'=>'map',              'label'=>'Facility Layout',     'href'=>'/views/superadmin/zones.php'],
        ['icon'=>'users',            'label'=>'User Management',     'href'=>'/views/superadmin/users.php'],
        ['icon'=>'banknote',         'label'=>'Rate Configuration',  'href'=>'/views/superadmin/rates.php'],
        ['icon'=>'scroll-text',      'label'=>'Audit Logs',          'href'=>'/views/superadmin/audit.php'],
        ['icon'=>'settings',         'label'=>'System Settings',     'href'=>'/views/superadmin/settings.php'],
      ];
    } elseif ($role === ROLE_ADMIN) {
      $menu = [
        ['icon'=>'monitor-play',     'label'=>'Live Monitor',     'href'=>'/views/admin/dashboard.php'],
        ['icon'=>'log-in',           'label'=>'Record Entry',     'href'=>'/views/admin/entry.php'],
        ['icon'=>'log-out',          'label'=>'Process Exit',     'href'=>'/views/admin/exit.php'],
        ['icon'=>'receipt',          'label'=>'Transactions',     'href'=>'/views/admin/transactions.php'],
        ['icon'=>'wallet',           'label'=>'My Shift Report',  'href'=>'/views/admin/shift.php'],
        ['icon'=>'activity',         'label'=>'System Health',    'href'=>'/views/admin/health.php'],
      ];
    }

    $current = $_SERVER['REQUEST_URI'];
    foreach ($menu as $item):
      $active = (strpos($current, $item['href']) !== false) ? 'active' : '';
    ?>
    <a class="nav-item <?= $active ?>" href="<?= BASE_URL . $item['href'] ?>">
      <i class="nav-icon" data-lucide="<?= $item['icon'] ?>"></i>
      <span class="nav-label"><?= $item['label'] ?></span>
    </a>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="user-avatar"><?= strtoupper(substr($_SESSION[SESSION_USER_NAME], 0, 1)) ?></div>
    <div class="user-info">
      <div class="user-name"><?= htmlspecialchars($_SESSION[SESSION_USER_NAME]) ?></div>
      <div class="user-role"><?= ucfirst($_SESSION[SESSION_USER_ROLE]) ?></div>
    </div>
    <a href="<?= BASE_URL ?>/api/auth_logout.php" title="Sign Out" style="color:var(--danger); margin-left:auto; display:flex;">
      <i data-lucide="log-out" style="width:18px"></i>
    </a>
  </div>
</aside>

<!-- ── Main ── -->
<div class="main-wrap">
  <header class="topbar">
    <div class="topbar-left">
      <button class="mobile-menu-btn" onclick="toggleSidebar()" style="display: none; background: none; border: none; cursor: pointer; color: var(--text-main); margin-right: 12px;">
        <i data-lucide="menu"></i>
      </button>
      
      <button class="sidebar-toggle desktop-only" onclick="toggleSidebar()">
        <i data-lucide="chevron-left" id="toggle-icon"></i>
      </button>
      
      <div class="topbar-search">
        <i data-lucide="search"></i>
        <input type="text" placeholder="Search plates, transactions, or slots...">
      </div>
    </div>

    <div class="topbar-right">
      <div class="live-indicator">
        <span class="live-dot"></span>
        SYSTEM LIVE
      </div>
      <div class="topbar-time" id="topbar-clock"></div>
      
      <div style="width: 1px; height: 24px; background: var(--border);"></div>
      
      <button class="sidebar-toggle" id="dark-mode-toggle" title="Toggle Theme">
        <i data-lucide="moon" style="width:18px" id="theme-icon"></i>
      </button>

      <button class="sidebar-toggle" title="Notifications">
        <i data-lucide="bell" style="width:18px"></i>
      </button>
    </div>
  </header>

  <script>
    // Theme Logic
    const body = document.body;
    const themeToggle = document.getElementById('dark-mode-toggle');
    const themeIcon = document.getElementById('theme-icon');

    if (localStorage.getItem('theme') === 'dark') {
      body.classList.add('dark');
      themeIcon.setAttribute('data-lucide', 'sun');
    }

    themeToggle.addEventListener('click', () => {
      body.classList.toggle('dark');
      const isDark = body.classList.contains('dark');
      localStorage.setItem('theme', isDark ? 'dark' : 'light');
      themeIcon.setAttribute('data-lucide', isDark ? 'sun' : 'moon');
      lucide.createIcons();
    });

    // Mobile Sidebar Toggle
    function toggleSidebar() {
        const body = document.body;
        const icon = document.querySelector('.mobile-menu-btn i');
        
        body.classList.toggle('sidebar-open');
        
        if (body.classList.contains('sidebar-open')) {
            if(icon) icon.setAttribute('data-lucide', 'x');
        } else {
            if(icon) icon.setAttribute('data-lucide', 'menu');
        }
        lucide.createIcons();
    }
  </script>

  <div class="breadcrumb" style="padding: 32px 40px 0 40px; display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-muted);">
    <i data-lucide="home" style="width:14px"></i>
    <span>/</span>
    <span style="color: var(--text-main); font-weight: 500;"><?= $pageTitle ?? 'Dashboard' ?></span>
  </div>

  <main class="main-content">
    <?= $content ?? '' ?>
  </main>
  
  <footer style="margin-top: auto; padding: 24px 40px; border-top: 1px solid var(--border); font-size: 12px; color: var(--text-muted); display: flex; justify-content: space-between;">
    <span>ParkSys Pro Enterprise v1.0.2</span>
    <span>&copy; <?= date('Y') ?> All Rights Reserved.</span>
  </footer>
</div>

<!-- Scripts -->
<script src="<?= BASE_URL ?>/assets/js/parksys.js"></script>
<script>lucide.createIcons();</script>
<script>
(function tick(){
  const el = document.getElementById('topbar-clock');
  if(el) el.textContent = new Date().toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
  setTimeout(tick, 1000);
})();

function toggleSidebar(){
  const sb = document.getElementById('sidebar');
  const icon = document.getElementById('toggle-icon');
  sb.classList.toggle('collapsed');
  
  if(sb.classList.contains('collapsed')) {
    icon.setAttribute('data-lucide', 'chevron-right');
  } else {
    icon.setAttribute('data-lucide', 'chevron-left');
  }
  lucide.createIcons();
}
</script>
</body>
</html>