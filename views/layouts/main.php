<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="base-url" content="<?= BASE_URL ?>">
<title><?= $pageTitle ?? 'Dashboard' ?> — ParkSys Pro</title>
<link href="<?= BASE_URL ?>/assets/css/parksys.css?v=<?= time() ?>" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    // ── Smart Navigation Toggle (Final Validated Version) ──
    function toggleSidebar() {
        const body = document.body;
        const sb = document.querySelector('.sidebar');
        const desktopIcon = document.getElementById('toggle-icon');
        const mobileIcon = document.querySelector('.mobile-menu-btn i');
        const isMobile = window.innerWidth <= 768;

        if (!sb) return;

        if (isMobile) {
            // Mobile Logic: Slide-out menu
            body.classList.toggle('sidebar-open');
            sb.classList.remove('collapsed'); // Force labels to show on mobile
            
            const isOpen = body.classList.contains('sidebar-open');
            if (isOpen) {
                sb.style.setProperty('left', '0', 'important');
                sb.style.setProperty('transform', 'none', 'important');
                sb.style.setProperty('visibility', 'visible', 'important');
                sb.style.setProperty('opacity', '1', 'important');
            } else {
                sb.style.removeProperty('left');
                sb.style.removeProperty('transform');
                sb.style.removeProperty('visibility');
                sb.style.removeProperty('opacity');
            }
            if (mobileIcon) mobileIcon.setAttribute('data-lucide', isOpen ? 'x' : 'menu');
        } else {
            // Desktop Logic: Collapse/Expand
            sb.classList.toggle('collapsed');
            const isCollapsed = sb.classList.contains('collapsed');
            if (desktopIcon) {
                desktopIcon.setAttribute('data-lucide', isCollapsed ? 'chevron-right' : 'chevron-left');
            }
            // Ensure mobile state is reset
            body.classList.remove('sidebar-open');
            sb.style.removeProperty('left');
        }

        if(typeof lucide !== 'undefined') lucide.createIcons();
    }

    // Close sidebar on overlay click
    document.addEventListener('click', (e) => {
        const overlay = document.querySelector('.sidebar-overlay');
        if (e.target === overlay) toggleSidebar();
    });
</script>
<style>
    /* Mobile Navigation System */
    @media (max-width: 768px) {
        .sidebar-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
            z-index: 9999; display: none;
            animation: fadeIn 0.2s ease-out;
        }
        body.sidebar-open .sidebar-overlay {
            display: block !important;
        }
        .sidebar-close {
            display: flex !important;
            align-items: center; justify-content: center;
            width: 36px; height: 36px;
            background: rgba(255,255,255,0.1);
            border-radius: 8px; color: #fff; cursor: pointer;
        }
        body.sidebar-open .sidebar {
            transform: none !important;
            left: 0 !important;
            visibility: visible !important;
            opacity: 1 !important;
            display: flex !important;
        }
        body.sidebar-open .nav-label {
            display: inline-block !important;
            margin-left: 12px !important;
            opacity: 1 !important;
        }
        body.sidebar-open .nav-item {
            justify-content: flex-start !important;
            padding: 12px 20px !important;
        }
    }
</style>
</head>
<body>

<div class="sidebar-overlay"></div>

<!-- ── Sidebar ── -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><i data-lucide="parking-circle"></i></div>
    <span class="brand-name">ParkSys Pro</span>
    <!-- Mobile Close Button -->
    <div class="sidebar-close" onclick="toggleSidebar()" style="display: none; margin-left: auto;">
      <i data-lucide="x"></i>
    </div>
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
      <button class="mobile-menu-btn" onclick="toggleSidebar()" title="Toggle Menu">
        <i data-lucide="menu"></i>
      </button>
      
      <button class="sidebar-toggle desktop-only" onclick="toggleSidebar()" title="Toggle Sidebar">
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
      
      <div style="width: 1px; height: 24px; background: var(--border); margin: 0 8px;"></div>
      
      <button class="sidebar-toggle" id="dark-mode-toggle" title="Toggle Theme">
        <i data-lucide="moon" style="width:18px" id="theme-icon"></i>
      </button>

      <button class="sidebar-toggle" title="Notifications">
        <i data-lucide="bell" style="width:18px"></i>
      </button>
    </div>
  </header>

  <div class="breadcrumb" style="padding: 32px 40px 0 40px; display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-muted);">
    <i data-lucide="home" style="width:14px"></i>
    <span>/</span>
    <span style="color: var(--text-main); font-weight: 500;"><?= $pageTitle ?? 'Dashboard' ?></span>
  </div>

  <script>
    // Theme & UI Logic
    const body = document.body;
    const themeToggle = document.getElementById('dark-mode-toggle');
    const themeIcon = document.getElementById('theme-icon');

    if (localStorage.getItem('theme') === 'dark') {
      body.classList.add('dark');
      if(themeIcon) themeIcon.setAttribute('data-lucide', 'sun');
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
          body.classList.toggle('dark');
          const isDark = body.classList.contains('dark');
          localStorage.setItem('theme', isDark ? 'dark' : 'light');
          if(themeIcon) themeIcon.setAttribute('data-lucide', isDark ? 'sun' : 'moon');
          if(typeof lucide !== 'undefined') lucide.createIcons();
        });
    }
  </script>

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
</script>
</body>
</html>