<?php
$code = $code ?? 404;
$title = $title ?? 'صفحه پیدا نشد';
$message = $message ?? 'متاسفانه صفحه‌ای که به دنبال آن بودید یافت نشد یا تغییر مکان داده است.';

if ($code == 500) {
    $title = $title ?? 'خطای داخلی سرور';
    $message = $message ?? 'متاسفانه مشکلی در سمت سرور رخ داده است. در حال بررسی و رفع آن هستیم.';
} elseif ($code == 403) {
    $title = $title ?? 'دسترسی غیرمجاز';
    $message = $message ?? 'شما اجازه دسترسی به این بخش را ندارید.';
}
?>
<div class="error-container d-column align-center just-center p-3 text-center gap-2">
    <div class="error-visual relative">
        <div class="error-code font-bold ltr"><?= $code ?></div>
    </div>

    <div class="error-content d-column gap-1">
        <h2 class="font-bold font-size-6 text-title"><?= htmlspecialchars($title) ?></h2>
        <p class="text-gray font-size-3 line-height-2"><?= htmlspecialchars($message) ?></p>
    </div>

    <?php if (isset($details) && !empty($details)): ?>
    <div class="error-details w-full mt-2 text-left ltr">
        <div class="bg-secondary p-2 radius-12 border overflow-hidden">
            <div class="font-bold mb-1 text-error font-size-1">Technical Details:</div>
            <pre class="font-size-1 text-gray scrollbar-hidden"><?= htmlspecialchars($details) ?></pre>
        </div>
    </div>
    <?php endif; ?>

    <div class="w-full d-flex-wrap just-center gap-1 mt-1">
        <a href="/" class="btn btn-primary radius-12 basis-150">
            <i data-lucide="house" class="icon-size-4"></i>
            <span>بازگشت به خانه</span>
        </a>
        <button onclick="window.history.length > 1 ? window.history.back() : window.location.href='/'" class="btn btn-secondary basis-150 radius-12">
            <i data-lucide="arrow-right" class="icon-size-4"></i>
            <span>صفحه قبلی</span>
        </button>
    </div>
</div>
