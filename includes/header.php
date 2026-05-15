<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'HR Dashboard'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome สำหรับ Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<!-- Sidebar Overlay (Mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">
            <i class="fa-solid fa-chart-pie"></i>
        </div>
        <div>
            <div class="sidebar-brand-text">HR Dashboard</div>
            <div class="sidebar-brand-sub">People Analytics</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-nav-label">Main Menu</div>
        <a href="index.php" class="sidebar-link <?php echo (($currentPage ?? '') === 'overview') ? 'active' : ''; ?>">
            <i class="fa-solid fa-gauge-high"></i>
            <span>Overview</span>
        </a>
        <a href="HeadCount.php" class="sidebar-link <?php echo (($currentPage ?? '') === 'headcount') ? 'active' : ''; ?>">
            <i class="fa-solid fa-users"></i>
            <span>HeadCount</span>
        </a>
        <a href="TurnoverRate.php" class="sidebar-link <?php echo (($currentPage ?? '') === 'turnover') ? 'active' : ''; ?>">
            <i class="fa-solid fa-user-minus"></i>
            <span>Turnover Rate</span>
        </a>
        <?php if (isset($_SESSION['aut_level']) && $_SESSION['aut_level'] == 99): ?>
        <div class="sidebar-nav-label">Administration</div>
        <a href="manage_auth.php" class="sidebar-link <?php echo (($currentPage ?? '') === 'manage_auth') ? 'active' : ''; ?>">
            <i class="fa-solid fa-shield-halved"></i>
            <span>Manage Auth</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-user">
        <div class="sidebar-avatar">
            <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
        </div>
        <div style="flex:1;min-width:0;">
            <div class="sidebar-user-name text-truncate"><?php echo isset($_SESSION['user_name']) ? iconv('TIS-620', 'UTF-8', $_SESSION['user_name']) : 'User'; ?></div>
            <div class="sidebar-user-role"><?php echo $_SESSION['role'] ?? 'Staff'; ?></div>
        </div>
        <a href="api/auth_logout.php" class="topbar-btn" title="Sign out" style="width:32px;height:32px;">
            <i class="fa-solid fa-right-from-bracket" style="font-size:0.8rem;"></i>
        </a>
    </div>
</aside>

<!-- Main Content Wrapper -->
<div class="main-content">
    <!-- Top Bar -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="topbar-toggle" onclick="toggleSidebarMobile()" aria-label="Toggle sidebar mobile">
                <i class="fa-solid fa-bars"></i>
            </button>
            <button class="desktop-toggle d-none d-xl-flex" onclick="toggleSidebarDesktop()" aria-label="Toggle sidebar desktop">
                <i class="fa-solid fa-bars-staggered"></i>
            </button>
            <div>
                <div class="page-title"><?php
                    $pageTitles = [
                        'overview' => 'Overview',
                        'headcount' => 'HeadCount Analytics',
                        'turnover' => 'Turnover Rate',
                        'manage_auth' => 'Manage Auth'
                    ];
                    echo $pageTitles[$currentPage ?? 'overview'] ?? 'Dashboard';
                ?></div>
                <div class="page-subtitle"><?php
                    $pageSubs = [
                        'overview' => 'ภาพรวมข้อมูลกำลังคนและแนวโน้มรายเดือน',
                        'headcount' => 'วิเคราะห์ข้อมูลกำลังคนเชิงลึกและการกระจายสมดุล',
                        'turnover' => 'วิเคราะห์อัตราการลาออกรายเดือน',
                        'manage_auth' => 'ควบคุมผู้ใช้งาน ระดับสิทธิ์ และสถานะ'
                    ];
                    echo $pageSubs[$currentPage ?? 'overview'] ?? '';
                ?></div>
            </div>
        </div>
        <div class="topbar-right">
            <button class="topbar-btn me-2" onclick="hardReload()" title="Hard Refresh (Clear Cache)">
                <i class="fa-solid fa-sync-alt"></i>
            </button>
            <div style="font-size:0.75rem;color:var(--text-muted);text-align:right;">
                <div style="font-weight:600;color:var(--text-secondary);"><?php echo date('d M Y'); ?></div>
            </div>
        </div>
    </header>

<script>
// Check localStorage on load
if (localStorage.getItem('sidebar-collapsed') === 'true') {
    document.body.classList.add('sidebar-collapsed');
}

function toggleSidebarMobile() {
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}

function toggleSidebarDesktop() {
    document.body.classList.toggle('sidebar-collapsed');
    const isCollapsed = document.body.classList.contains('sidebar-collapsed');
    localStorage.setItem('sidebar-collapsed', isCollapsed);
    
    // Trigger window resize for charts to recalculate
    setTimeout(() => {
        window.dispatchEvent(new Event('resize'));
    }, 400);
}

function hardReload() {
    // Add a timestamp to force cache bust
    const url = new URL(window.location.href);
    url.searchParams.set('reload', Date.now());
    window.location.href = url.toString();
}

// Global for overlay click
function toggleSidebar() { toggleSidebarMobile(); }
</script>
