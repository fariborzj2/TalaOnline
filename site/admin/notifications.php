<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
check_permission("settings.view");

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_permission("settings.edit");

    $settings = [
        'webpush_enabled',
        'webpush_public_key',
        'webpush_private_key',
        'webpush_subject'
    ];

    foreach ($settings as $key) {
        if (isset($_POST[$key])) {
            set_setting($key, $_POST[$key]);
        }
    }
    $message = 'تنظیمات با موفقیت ذخیره شد.';
}

$page_title = 'تنظیمات اعلان‌ها';
include __DIR__ . '/layout/header.php';
?>

<div class="glass-card rounded-xl p-8 border border-slate-200">
    <form method="POST" class="max-w-4xl space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-2">
                <label class="text-xs font-black text-slate-700">فعال‌سازی اعلان‌های وب</label>
                <select name="webpush_enabled" class="w-full">
                    <option value="1" <?= get_setting('webpush_enabled') === '1' ? 'selected' : '' ?>>بله</option>
                    <option value="0" <?= get_setting('webpush_enabled') === '0' ? 'selected' : '' ?>>خیر</option>
                </select>
            </div>

            <div class="space-y-2">
                <label class="text-xs font-black text-slate-700">موضوع VAPID (Email/URL)</label>
                <input type="text" name="webpush_subject" value="<?= htmlspecialchars(get_setting('webpush_subject', 'mailto:admin@example.com')) ?>" class="w-full">
            </div>

            <div class="space-y-2 md:col-span-2">
                <label class="text-xs font-black text-slate-700">کلید عمومی VAPID</label>
                <textarea name="webpush_public_key" rows="3" class="w-full font-mono text-xs"><?= htmlspecialchars(get_setting('webpush_public_key')) ?></textarea>
            </div>

            <div class="space-y-2 md:col-span-2">
                <label class="text-xs font-black text-slate-700">کلید خصوصی VAPID</label>
                <textarea name="webpush_private_key" rows="3" class="w-full font-mono text-xs"><?= htmlspecialchars(get_setting('webpush_private_key')) ?></textarea>
            </div>
        </div>

        <div class="pt-6 border-t border-slate-100">
            <button type="submit" class="btn-v3 btn-v3-primary px-12">ذخیره تنظیمات</button>
        </div>
    </form>
</div>

<div class="mt-8 glass-card rounded-xl p-8 border border-slate-200">
    <h3 class="text-lg font-black text-slate-900 mb-6">آمار ارسال اعلان‌ها</h3>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <?php
        $stats = $pdo->query("SELECT event_type, COUNT(*) as total FROM notification_analytics GROUP BY event_type")->fetchAll(PDO::FETCH_KEY_PAIR);
        ?>
        <div class="bg-indigo-50 p-4 rounded-xl border border-indigo-100">
            <p class="text-[10px] font-black text-indigo-400 uppercase">ارسال شده</p>
            <p class="text-2xl font-black text-indigo-700"><?= number_format($stats['sent'] ?? 0) ?></p>
        </div>
        <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-100">
            <p class="text-[10px] font-black text-emerald-400 uppercase">کلیک شده</p>
            <p class="text-2xl font-black text-emerald-700"><?= number_format($stats['clicked'] ?? 0) ?></p>
        </div>
        <div class="bg-rose-50 p-4 rounded-xl border border-rose-100">
            <p class="text-[10px] font-black text-rose-400 uppercase">خطا</p>
            <p class="text-2xl font-black text-rose-700"><?= number_format($stats['failed'] ?? 0) ?></p>
        </div>
        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
            <p class="text-[10px] font-black text-slate-400 uppercase">مشترکین فعال</p>
            <p class="text-2xl font-black text-slate-700"><?= number_format($pdo->query("SELECT COUNT(*) FROM push_subscriptions")->fetchColumn()) ?></p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
