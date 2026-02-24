<?php
/**
 * Offline Fallback Page
 */
$hide_sidebar = true;
$hide_side_menu = true;
$hide_mobile_nav = false;
$hide_layout_h1 = true;
$body_class = 'offline-page';

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/core/View.php';

$page_title = 'اتصال برقرار نیست';
$site_title = get_setting('site_title', 'طلا آنلاین');
$site_description = get_setting('site_description', '');

ob_start();
?>
<div class="text-center pd-lg d-column align-center gap-2 py-5">
    <div class="bg-secondary p-3 radius-50 d-flex align-center just-center mb-2" style="width: 120px; height: 120px; margin: 0 auto;">
        <i data-lucide="wifi-off" style="width: 64px; height: 64px; color: var(--color-gray);"></i>
    </div>
    <h2 class="font-bold font-size-6 mt-1">شما آفلاین هستید</h2>
    <p class="text-gray font-size-3 max-w400 mx-auto">
        در حال حاضر به اینترنت دسترسی ندارید. لطفاً وضعیت شبکه خود را بررسی کنید.
    </p>
    <div class="mt-2">
        <a href="/" class="btn btn-primary radius-12 d-inline-flex gap-1 align-center px-4">
            <i data-lucide="refresh-cw" class="icon-size-4"></i>
            <span>تلاش مجدد</span>
        </a>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../includes/views/layouts/main.php';
