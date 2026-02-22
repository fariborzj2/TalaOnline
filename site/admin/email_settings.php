<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
check_permission("settings.view");

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Security violation: Invalid CSRF token.');
    }

    if (isset($_POST['save_general'])) {
        $mail_enabled = isset($_POST['mail_enabled']) ? '1' : '0';
        $mail_sender_name = $_POST['mail_sender_name'] ?? '';
        $mail_sender_email = $_POST['mail_sender_email'] ?? '';

        set_setting('mail_enabled', $mail_enabled);
        set_setting('mail_sender_name', $mail_sender_name);
        set_setting('mail_sender_email', $mail_sender_email);

        $message = 'تنظیمات عمومی ایمیل با موفقیت ذخیره شد.';
    } elseif (isset($_POST['save_template'])) {
        $template_id = $_POST['template_id'];
        $subject = $_POST['subject'] ?? '';
        $body = $_POST['body'] ?? '';

        try {
            $stmt = $pdo->prepare("UPDATE email_templates SET subject = ?, body = ? WHERE id = ?");
            $stmt->execute([$subject, $body, $template_id]);
            $message = 'قالب ایمیل با موفقیت بروزرسانی شد.';
        } catch (Exception $e) {
            $error = 'خطا در بروزرسانی قالب: ' . $e->getMessage();
        }
    }
}

$mail_enabled = get_setting('mail_enabled', '1');
$mail_sender_name = get_setting('mail_sender_name', get_setting('site_title', 'Tala Online'));
$mail_sender_email = get_setting('mail_sender_email', 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

$templates = $pdo->query("SELECT * FROM email_templates ORDER BY id ASC")->fetchAll();

$page_title = 'تنظیمات ایمیل';
$page_subtitle = 'مدیریت پیکربندی ارسال ایمیل و ویرایش قالب‌های اطلاع‌رسانی سیستم';

include __DIR__ . '/layout/header.php';
?>

<?php if ($message): ?>
    <div class="mb-8 animate-bounce-in">
        <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 flex items-center gap-3 text-emerald-700">
            <div class="w-8 h-8 bg-emerald-500 text-white rounded-lg flex items-center justify-center">
                <i data-lucide="check" class="w-5 h-5"></i>
            </div>
            <span class="font-bold"><?= $message ?></span>
        </div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="mb-8 animate-bounce-in">
        <div class="bg-rose-50 border border-rose-100 rounded-xl p-4 flex items-center gap-3 text-rose-700">
            <div class="w-8 h-8 bg-rose-500 text-white rounded-lg flex items-center justify-center">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
            </div>
            <span class="font-bold"><?= $error ?></span>
        </div>
    </div>
<?php endif; ?>

<div class="max-w-4xl space-y-8 pb-10">
    <!-- General Email Settings -->
    <form method="POST" class="glass-card rounded-xl overflow-hidden border border-slate-200">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="px-8 py-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/30">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-indigo-600 border border-indigo-50">
                <i data-lucide="mail" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-lg font-black text-slate-800">تنظیمات ارسال</h2>
                <p class="text-[10px] text-slate-400 font-bold uppercase ">Email Configuration</p>
            </div>
        </div>
        <div class="p-8 space-y-6">
            <div class="form-group flex items-center gap-4">
                <label class="mb-0">فعال‌سازی ارسال ایمیل</label>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="mail_enabled" value="1" class="sr-only peer" <?= $mail_enabled === '1' ? 'checked' : '' ?>>
                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label>نام فرستنده</label>
                    <div class="input-icon-wrapper">
                        <span class="icon"><i data-lucide="user" class="w-4 h-4"></i></span>
                        <input type="text" name="mail_sender_name" value="<?= htmlspecialchars($mail_sender_name) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>ایمیل فرستنده</label>
                    <div class="input-icon-wrapper">
                        <span class="icon"><i data-lucide="at-sign" class="w-4 h-4"></i></span>
                        <input type="email" name="mail_sender_email" value="<?= htmlspecialchars($mail_sender_email) ?>" required class="ltr-input">
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" name="save_general" class="btn-v3 btn-v3-primary">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    ذخیره تنظیمات عمومی
                </button>
            </div>
        </div>
    </form>

    <!-- Email Templates -->
    <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
        <div class="px-8 py-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/30">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-amber-600 border border-amber-50">
                <i data-lucide="layout-template" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-lg font-black text-slate-800">قالب‌های ایمیل</h2>
                <p class="text-[10px] text-slate-400 font-bold uppercase ">Email Templates</p>
            </div>
        </div>
        <div class="p-8 space-y-8">
            <?php foreach ($templates as $template): ?>
                <form method="POST" class="p-6 bg-slate-50/50 rounded-xl border border-slate-100 space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="template_id" value="<?= $template['id'] ?>">
                    <div class="flex items-center justify-between mb-2">
                        <span class="px-3 py-1 bg-white border border-slate-200 rounded-lg text-xs font-black text-slate-500 uppercase"><?= $template['slug'] ?></span>
                        <span class="text-[10px] text-slate-400 font-bold">آخرین بروزرسانی: <?= jalali_date($template['updated_at']) ?></span>
                    </div>

                    <div class="form-group">
                        <label>موضوع ایمیل</label>
                        <input type="text" name="subject" value="<?= htmlspecialchars($template['subject']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>متن ایمیل (HTML مجاز است)</label>
                        <textarea name="body" rows="6" class="font-mono text-sm"><?= htmlspecialchars($template['body']) ?></textarea>
                        <div class="mt-2 p-3 bg-amber-50 rounded-lg border border-amber-100">
                            <p class="text-[10px] text-amber-700 font-bold mb-1">متغیرهای در دسترس:</p>
                            <div class="flex flex-wrap gap-2">
                                <code class="text-[9px] bg-white px-1.5 py-0.5 rounded border border-amber-200">{site_title}</code>
                                <code class="text-[9px] bg-white px-1.5 py-0.5 rounded border border-amber-200">{base_url}</code>
                                <code class="text-[9px] bg-white px-1.5 py-0.5 rounded border border-amber-200">{name}</code>
                                <?php if ($template['slug'] === 'verification'): ?>
                                    <code class="text-[9px] bg-white px-1.5 py-0.5 rounded border border-amber-200">{verification_link}</code>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" name="save_template" class="btn-v3 btn-v3-outline text-amber-600 border-amber-200 hover:bg-amber-50">
                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                            بروزرسانی قالب
                        </button>
                    </div>
                </form>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
