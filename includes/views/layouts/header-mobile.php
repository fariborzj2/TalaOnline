<header class="mobile-header mobile-only">
    <div class="mobile-header-inner">
        <div class="mobile-header-notifications">
            <i data-lucide="bell" class="icon-size-6"></i>
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
            <div class="mobile-pulse-container">
                <span class="mobile-pulse-dot"></span>
            </div>
            <span class="mobile-live-text">زنده</span>
        </div>
        <div class="mobile-date-wrapper">
            <span class="mobile-today-text">امروز </span>
            <span class="mobile-date-text"><?= jalali_date('now', 'weekday') ?></span>
        </div>
    </div>
</header>
