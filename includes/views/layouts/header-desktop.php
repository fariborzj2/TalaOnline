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

        <div class="desktop-header-notifications">
            <i data-lucide="bell" class="icon-size-3"></i>
        </div>

        <div class="desktop-header-date">
            <div class="desktop-live-status">
                <div class="desktop-pulse-container">
                    <span class="desktop-pulse-dot"></span>
                </div>
            </div>
            <div class="desktop-date-wrapper">
                <span class="desktop-date-text"><?= jalali_date('now', 'weekday') ?></span>
                <i data-lucide="calendar-days" class="desktop-calendar-icon"></i>
            </div>
        </div>
    </div>
</header>
