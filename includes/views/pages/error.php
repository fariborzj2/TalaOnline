<?php
$code = $code ?? 404;
$title = $title ?? 'صفحه پیدا نشد';
$message = $message ?? 'متاسفانه صفحه‌ای که به دنبال آن بودید یافت نشد یا تغییر مکان داده است.';
$icon = $icon ?? 'search';

if ($code == 500) {
    $title = $title ?? 'خطای داخلی سرور';
    $message = $message ?? 'متاسفانه مشکلی در سمت سرور رخ داده است. در حال بررسی و رفع آن هستیم.';
    $icon = 'alert-triangle';
} elseif ($code == 403) {
    $title = $title ?? 'دسترسی غیرمجاز';
    $message = $message ?? 'شما اجازه دسترسی به این بخش را ندارید.';
    $icon = 'shield-off';
}
?>

<div class="error-container d-column align-center just-center p-3 text-center gap-2" style="min-height: 60vh;">
    <div class="error-visual relative" style="height: 180px;">
        <div class="error-code font-bold ltr" style="font-size: 10rem; opacity: 0.05; line-height: 1;"><?= $code ?></div>
        <div class="error-icon-wrapper absolute bg-block radius-24 shadow-lg d-flex align-center just-center" style="width: 120px; height: 120px; top: 50%; left: 50%; transform: translate(-50%, -50%);">
            <i data-lucide="<?= $icon ?>" style="width: 60px; height: 60px; color: var(--color-primary);"></i>
        </div>
    </div>

    <div class="error-content d-column gap-1" style="max-width: 500px;">
        <h2 class="font-bold font-size-6 text-title"><?= htmlspecialchars($title) ?></h2>
        <p class="text-gray font-size-3 line-height-2"><?= htmlspecialchars($message) ?></p>
    </div>

    <?php if (isset($details) && !empty($details)): ?>
    <div class="error-details w-full mt-2 text-left ltr" style="max-width: 800px;">
        <div class="bg-secondary p-2 radius-12 border overflow-hidden">
            <div class="font-bold mb-1 text-error font-size-1">Technical Details:</div>
            <pre class="font-size-1 text-gray scrollbar-hidden" style="white-space: pre-wrap; word-break: break-all; overflow-x: auto; max-height: 300px;"><?= htmlspecialchars($details) ?></pre>
        </div>
    </div>
    <?php endif; ?>

    <div class="error-actions d-flex-wrap just-center gap-1 mt-1">
        <a href="/" class="btn btn-primary radius-12 px-2 gap-05">
            <i data-lucide="house" class="icon-size-4"></i>
            <span>بازگشت به صفحه اصلی</span>
        </a>
        <button onclick="window.history.length > 1 ? window.history.back() : window.location.href='/'" class="btn btn-secondary radius-12 px-2 gap-05">
            <i data-lucide="arrow-right" class="icon-size-4"></i>
            <span>صفحه قبلی</span>
        </button>
    </div>
</div>
