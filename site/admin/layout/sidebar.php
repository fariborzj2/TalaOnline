<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i data-lucide="sparkles" style="color: white;"></i>
        </div>
        <div class="sidebar-title">طلا آنلاین</div>
    </div>

    <ul class="nav-menu">
        <li class="nav-item">
            <a href="index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>">
                <i data-lucide="layout-dashboard"></i>
                <span>داشبورد</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="items.php" class="nav-link <?= $current_page == 'items.php' ? 'active' : '' ?>">
                <i data-lucide="coins"></i>
                <span>مدیریت آیتم‌ها</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="platforms.php" class="nav-link <?= $current_page == 'platforms.php' ? 'active' : '' ?>">
                <i data-lucide="layers"></i>
                <span>پلتفرم‌ها</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="settings.php" class="nav-link <?= $current_page == 'settings.php' ? 'active' : '' ?>">
                <i data-lucide="settings"></i>
                <span>تنظیمات سیستم</span>
            </a>
        </li>
        <li class="nav-item" style="margin-top: auto;">
            <a href="logout.php" class="nav-link logout-btn">
                <i data-lucide="log-out"></i>
                <span>خروج</span>
            </a>
        </li>
    </ul>
</aside>
