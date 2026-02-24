<div class="header-inner mobile-only">
    <div class="header-notifications">
        <i data-lucide="bell" class="icon-size-6"></i>
    </div>

    <div class="header-logo">
        <a href="<?= get_base_url(); ?>">
            <img src="<?= versioned_asset('/assets/images/logo.svg') ?>" alt="طلا آنلاین" height="28">
        </a>
    </div>

    <div class="header-profile user-menu-btn" id="user-menu-btn-mobile">
        <div class="avatar-wrapper radius-50">
            <?php if (!empty($_SESSION['user_avatar'])): ?>
                <img src="<?= htmlspecialchars($_SESSION['user_avatar']) ?>" class="user-avatar-nav">
            <?php else: ?>
                <i data-lucide="user" class="icon-size-5"></i>
            <?php endif; ?>
        </div>
        <!-- Mobile name is usually hidden via CSS but we include it for JS syncing if needed, though here we might not need it -->
        <span class="user-name user-menu-text d-none" id="user-menu-text-mobile">
            <?= isset($_SESSION['user_id']) ? htmlspecialchars($_SESSION['user_name']) : 'ورود / عضویت' ?>
        </span>
    </div>

    <div class="header-date">
        <div class="live-status">
            <div class="pulse-container">
                <span class="pulse-dot"></span>
            </div>
            <span class="live-text">زنده</span>
        </div>
        <div class="date-wrapper">
            <span class="today-text">امروز </span>
            <span class="date-text"><?= jalali_date('now', 'weekday') ?></span>
        </div>
    </div>
</div>
