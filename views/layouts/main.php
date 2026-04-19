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
    function toggleSidebar() {
        const body = document.body;
        const isMobile = window.innerWidth <= 768;
        const sb = document.getElementById('sidebar');
        const desktopIcon = document.getElementById('toggle-icon');
        const mobileIcon = document.querySelector('.mobile-menu-btn i');
        if (!sb) return;
        if (isMobile) {
            body.classList.toggle('sidebar-open');
            const isOpen = body.classList.contains('sidebar-open');
            if (mobileIcon) mobileIcon.setAttribute('data-lucide', isOpen ? 'x' : 'menu');
        } else {
            sb.classList.toggle('collapsed');
            const isCollapsed = sb.classList.contains('collapsed');
            if (desktopIcon) desktopIcon.setAttribute('data-lucide', isCollapsed ? 'chevron-right' : 'chevron-left');
            body.classList.remove('sidebar-open');
        }
        if(typeof lucide !== 'undefined') lucide.createIcons();
    }
    document.addEventListener('click', (e) => {
        const overlay = document.querySelector('.sidebar-overlay');
        if (e.target === overlay) toggleSidebar();
    });
</script>
</head>
<body>

<div class="sidebar-overlay"></div>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><i data-lucide="parking-circle"></i></div>
    <span class="brand-name">ParkSys Pro</span>
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
        ['icon'=>'terminal',          'label'=>'Command Center',      'href'=>'/views/superadmin/command_center.php'],
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
      $fullHref = BASE_URL . $item['href'];
      $active = (strpos($current, $item['href']) !== false) ? 'active' : '';
    ?>
    <a class="nav-item <?= $active ?>" href="<?= $fullHref ?>">
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
    <button onclick="showShiftSummary()" title="Close Shift & Sign Out" style="background:none; border:none; color:var(--danger); margin-left:auto; display:flex; cursor:pointer; padding:4px;">
      <i data-lucide="power" style="width:18px"></i>
    </button>
  </div>
</aside>

<div class="main-wrap">
  <header class="topbar">
    <div class="topbar-left">
      <button class="mobile-menu-btn" onclick="toggleSidebar()" title="Toggle Menu">
        <i data-lucide="menu"></i>
      </button>
      <button class="sidebar-toggle desktop-only" onclick="toggleSidebar()" title="Toggle Sidebar" style="background:none; border:none; cursor:pointer; color:var(--text-muted); padding:8px; display:flex;">
        <i data-lucide="chevron-left" id="toggle-icon" style="width:20px;"></i>
      </button>
      <div class="topbar-search" style="position: relative;">
        <i data-lucide="search"></i>
        <input type="text" id="global-search" placeholder="Search plates, transactions, or staff..." autocomplete="off">
        <div id="search-results-dropdown" class="card" style="display: none; position: absolute; top: 110%; left: 0; right: 0; z-index: 10000; padding: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid var(--border); max-height: 400px; overflow-y: auto;"></div>
      </div>
    </div>

    <div class="topbar-right">
      <div class="live-indicator"><span class="live-dot"></span>SYSTEM LIVE</div>
      <div class="topbar-time" id="topbar-clock"></div>
      <div style="width: 1px; height: 24px; background: var(--border); margin: 0 8px;"></div>
      <button class="sidebar-toggle" id="dark-mode-toggle" title="Toggle Theme">
        <i data-lucide="moon" style="width:18px" id="theme-icon"></i>
      </button>
      <button class="sidebar-toggle" id="notif-toggle" title="Notifications" style="position: relative;">
        <i data-lucide="bell" style="width:18px"></i>
        <span id="notif-badge" style="position: absolute; top: 4px; right: 4px; width: 8px; height: 8px; background: var(--danger); border-radius: 50%; display: none;"></span>
      </button>
      <div class="notif-dropdown" id="notif-dropdown">
        <div class="notif-header">
          <span>Notifications</span>
          <span onclick="clearNotifications()" style="font-size: 10px; color: var(--primary); cursor: pointer; font-weight: 800;">Clear All</span>
        </div>
        <div id="notif-list" style="max-height: 400px; overflow-y: auto;">
            <div class="notif-item unread">
                <div class="icon" style="background: #ecfdf5; color: var(--success);"><i data-lucide="shield-check" style="width:16px"></i></div>
                <div class="content"><div class="title">System Secure</div><div class="desc">All gate sensors optimal.</div><div class="time">Just Now</div></div>
            </div>
        </div>
      </div>
    </div>
  </header>

  <script>
    // Universal Search JS
    const sInput = document.getElementById('global-search');
    const sDropdown = document.getElementById('search-results-dropdown');
    let sTimeout;
    if (sInput) {
        sInput.addEventListener('input', (e) => {
            clearTimeout(sTimeout);
            const q = e.target.value.trim();
            if (q.length < 2) { sDropdown.style.display = 'none'; return; }
            sTimeout = setTimeout(() => {
                fetch('<?= BASE_URL ?>/api/universal_search.php?q=' + encodeURIComponent(q))
                    .then(r => r.json())
                    .then(data => {
                        if (data.results && data.results.length > 0) {
                            sDropdown.innerHTML = data.results.map(r => `
                                <a href="${r.link}" style="display: flex; flex-direction: column; padding: 10px 12px; text-decoration: none; border-radius: 8px; transition: background 0.2s;">
                                    <span style="font-size: 13px; font-weight: 700; color: var(--text-main);">${r.title}</span>
                                    <span style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">${r.type}</span>
                                </a>
                            `).join('');
                            sDropdown.style.display = 'block';
                            sDropdown.querySelectorAll('a').forEach(a => {
                                a.onmouseover = () => a.style.background = 'var(--bg)';
                                a.onmouseout = () => a.style.background = 'transparent';
                            });
                        } else {
                            sDropdown.innerHTML = '<div style="padding: 20px; text-align: center; color: var(--text-muted); font-size: 12px;">No results found</div>';
                            sDropdown.style.display = 'block';
                        }
                    });
            }, 300);
        });
        document.addEventListener('click', (e) => {
            if (!sInput.contains(e.target) && !sDropdown.contains(e.target)) sDropdown.style.display = 'none';
        });
    }

    // Notifications & Theme Logic
    const notifToggle = document.getElementById('notif-toggle');
    const notifDropdown = document.getElementById('notif-dropdown');
    function clearNotifications() {
        document.getElementById('notif-list').innerHTML = '<div style="padding: 40px; text-align: center; color: var(--text-muted); font-size: 12px;">No notifications</div>';
        document.getElementById('notif-badge').style.display = 'none';
        lucide.createIcons();
    }
    if(notifToggle) {
        notifToggle.addEventListener('click', (e) => { e.stopPropagation(); notifDropdown.classList.toggle('show'); });
        document.addEventListener('click', () => notifDropdown.classList.remove('show'));
    }
    const themeToggle = document.getElementById('dark-mode-toggle');
    if(themeToggle) {
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark');
            const isDark = document.body.classList.contains('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            document.getElementById('theme-icon').setAttribute('data-lucide', isDark ? 'sun' : 'moon');
            lucide.createIcons();
        });
    }
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark');
        document.getElementById('theme-icon').setAttribute('data-lucide', 'sun');
    }
  </script>

  <main class="main-content">
    <div class="breadcrumb" style="margin-bottom: 24px; display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-muted);">
      <i data-lucide="home" style="width:14px"></i>
      <span>/</span>
      <span style="color: var(--text-main); font-weight: 500;"><?= $pageTitle ?? 'Dashboard' ?></span>
    </div>
    <?= $content ?? '' ?>
  </main>
  
  <footer style="margin-top: auto; padding: 24px 40px; border-top: 1px solid var(--border); font-size: 12px; color: var(--text-muted); display: flex; justify-content: space-between;">
    <span>ParkSys Pro Enterprise v1.0.2</span>
    <span>&copy; <?= date('Y') ?> All Rights Reserved.</span>
  </footer>
</div>

<script src="<?= BASE_URL ?>/assets/js/parksys.js"></script>
<script>lucide.createIcons();</script>
<script>
(function tick(){
  const el = document.getElementById('topbar-clock');
  if(el) el.textContent = new Date().toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
  setTimeout(tick, 1000);
})();
</script>

<!-- Shift Summary Modal -->
<div id="shift-modal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.8); backdrop-filter:blur(8px); z-index:11000; align-items:center; justify-content:center; padding:20px;">
  <div style="background:var(--sidebar-bg); width:100%; max-width:440px; border-radius:20px; border:1px solid var(--border); overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);">
    <div style="padding:32px 32px 24px 32px; text-align:center;">
      <div style="width:56px; height:56px; background:var(--primary-light); color:var(--primary); border-radius:14px; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
        <i data-lucide="clipboard-check" style="width:28px; height:28px;"></i>
      </div>
      <h2 style="font-size:20px; font-weight:800; color:var(--text-main); margin-bottom:4px;">End of Shift Report</h2>
      <p style="font-size:13px; color:var(--text-muted);" id="shift-date"></p>
    </div>
    <div style="padding:0 32px 32px 32px;">
      <div style="background:var(--bg); border:1px solid var(--border); border-radius:12px; padding:20px; margin-bottom:24px;">
        <div style="display:flex; justify-content:space-between; margin-bottom:16px; padding-bottom:12px; border-bottom:1px solid var(--border);">
          <span style="font-size:12px; font-weight:600; color:var(--text-muted);">Total Revenue</span>
          <span style="font-size:16px; font-weight:800; color:var(--primary);" id="shift-rev">₱0.00</span>
        </div>
      </div>
      <div style="display:grid; gap:12px;">
        <button onclick="window.open('<?= BASE_URL ?>/views/admin/print_remittance.php', '_blank')" class="btn" style="width:100%; padding:14px; background:var(--primary-light); color:var(--primary); border:1px solid var(--primary); font-weight:700;">
          <i data-lucide="printer" style="width:16px; margin-right:8px; vertical-align:middle;"></i> Print Remittance Slip
        </button>
        <button onclick="location.href='<?= BASE_URL ?>/api/auth_logout.php'" class="btn btn-primary" style="width:100%; padding:14px; background:var(--danger); color:#fff; border:none;">Confirm Handover & Logout</button>
        <button onclick="document.getElementById('shift-modal').style.display='none'" style="background:none; border:none; color:var(--text-muted); font-size:13px; font-weight:600; cursor:pointer; padding:8px;">Back to Dashboard</button>
      </div>
    </div>
  </div>
</div>

<script>
function showShiftSummary() {
    const modal = document.getElementById('shift-modal');
    fetch('<?= BASE_URL ?>/api/get_shift_summary.php')
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                document.getElementById('shift-date').textContent = res.date + ' — Staff: ' + res.staff;
                document.getElementById('shift-rev').textContent = '₱' + res.metrics.total_revenue.toLocaleString(undefined, {minimumFractionDigits:2});
                modal.style.display = 'flex';
                lucide.createIcons();
            }
        });
}
</script>
</body>
</html>