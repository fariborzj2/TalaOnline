<header class="mobile-header mobile-only">
    <div class="mobile-header-inner">
        <div class="mobile-header-notifications" id="notification-btn-mobile">
            <i data-lucide="bell" class="icon-size-6"></i>
            <span class="notification-badge d-none" id="notification-badge-mobile">0</span>

            <div class="notification-dropdown" id="notification-dropdown-mobile" style="right: 0; left: auto; width: 280px;">
                <div class="notification-header">
                    <h3>اعلان‌ها</h3>
                    <button class="text-primary font-size-0-8" id="mark-all-read-mobile">خواندن همه</button>
                </div>
                <div class="notification-list" id="notification-list-mobile">
                    <div class="text-center py-4 text-gray">در حال بارگذاری...</div>
                </div>
            </div>
        </div>

        <div class="mobile-header-logo">
            <a href="<?= get_base_url(); ?>">
                <img src="<?= versioned_asset('/assets/images/logo-larg.svg') ?>" alt="طلا آنلاین" height="24">
            </a>
        </div>

        <div class="mobile-header-profile user-menu-btn" id="user-menu-btn-mobile">
            <div class="mobile-avatar-wrapper radius-50">
                <?php if (!empty($_SESSION['user_avatar'])): ?>
                    <img src="<?= htmlspecialchars($_SESSION['user_avatar']) ?>" class="user-avatar-nav">
                <?php else: ?>
                    <i data-lucide="user" class="icon-size-5"></i>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="mobile-header-date">
        <div class="mobile-live-status">
            <div class="pulse-container">
                <span class="pulse-dot"></span>
            </div>
            <span class="mobile-date-text">امروز <?= jalali_date('now', 'weekday') ?></span>
        </div>
        <div>
            <div class="pointer d-flex align-center font-bold" onclick="window.history.length > 1 ? window.history.back() : window.location.href='/'">
                <span>بازگشت</span>
                <i data-lucide="chevron-left" class="icon-size-3"></i>
            </div>
        </div>
    </div>
</header>
