<header class="desktop-header desktop-only">
    <div class="desktop-header-inner">
        <div class="desktop-header-profile user-menu-btn" id="user-menu-btn-desktop">
            <div class="desktop-avatar-wrapper radius-50">
                <?php if (!empty($_SESSION['user_avatar'])): ?>
                    <img src="<?= htmlspecialchars($_SESSION['user_avatar']) ?>" class="user-avatar-nav">
                <?php else: ?>
                    <i data-lucide="user" class="icon-size-5"></i>
                <?php endif; ?>
            </div>
            <span class="desktop-user-name user-menu-text" id="user-menu-text-desktop">
                <?= isset($_SESSION['user_id']) ? htmlspecialchars($_SESSION['user_name']) : 'ورود / عضویت' ?>
            </span>
        </div>

        <div class="desktop-header-notifications" id="notification-btn-desktop">
            <i data-lucide="bell" class="icon-size-3"></i>
            <span class="notification-badge d-none" id="notification-badge-desktop">0</span>

            <div class="notification-dropdown" id="notification-dropdown-desktop">
                <div class="notification-header">
                    <h3>اعلان‌ها</h3>
                    <button class="text-primary font-size-0-8" id="mark-all-read-desktop">خواندن همه</button>
                </div>
                <div class="notification-list" id="notification-list-desktop">
                    <div class="text-center py-4 text-gray">در حال بارگذاری...</div>
                </div>
                <div class="notification-footer">
                    <?php
                        $logged_user_id = $_SESSION['user_id'] ?? 0;
                        $logged_username = $_SESSION['user_username'] ?? 'user';
                        $profile_url = "/profile/$logged_user_id/" . urlencode($logged_username);
                    ?>
                    <a href="<?= $profile_url ?>?tab=notifications" class="notification-link-dynamic">مشاهده همه اعلان‌ها</a>
                </div>
            </div>
        </div>

        <div class="desktop-header-date">
            <div class="desktop-live-status">
                <div class="pulse-container">
                    <span class="pulse-dot"></span>
                </div>
            </div>
            <div class="desktop-date-wrapper">
                <span class="desktop-date-text"><?= jalali_date('now', 'weekday') ?></span>
            </div>
        </div>
    </div>
</header>
