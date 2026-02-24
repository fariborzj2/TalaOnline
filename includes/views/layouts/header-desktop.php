<div class="header-inner desktop-only">
    <div class="header-profile user-menu-btn" id="user-menu-btn-desktop">
        <div class="avatar-wrapper radius-50">
            <?php if (!empty($_SESSION['user_avatar'])): ?>
                <img src="<?= htmlspecialchars($_SESSION['user_avatar']) ?>" class="user-avatar-nav">
            <?php else: ?>
                <i data-lucide="user" class="icon-size-5"></i>
            <?php endif; ?>
        </div>
        <span class="user-name user-menu-text" id="user-menu-text-desktop">
            <?= isset($_SESSION['user_id']) ? htmlspecialchars($_SESSION['user_name']) : 'ورود / عضویت' ?>
        </span>
    </div>

    <div class="header-notifications">
        <i data-lucide="bell" class="icon-size-3"></i>
    </div>

    <div class="header-date">
        <div class="live-status">
            <div class="pulse-container">
                <span class="pulse-dot"></span>
            </div>
        </div>
        <div class="date-wrapper">
            <span class="date-text"><?= jalali_date('now', 'weekday') ?></span>
            <i data-lucide="calendar-days" class="calendar-icon"></i>
        </div>
    </div>
</div>
