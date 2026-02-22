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
        $mail_driver = $_POST['mail_driver'] ?? 'mail';
        $mail_sender_name = $_POST['mail_sender_name'] ?? '';
        $mail_sender_email = $_POST['mail_sender_email'] ?? '';

        set_setting('mail_enabled', $mail_enabled);
        set_setting('mail_driver', $mail_driver);
        set_setting('mail_sender_name', $mail_sender_name);
        set_setting('mail_sender_email', $mail_sender_email);

        if ($mail_driver === 'smtp') {
            set_setting('smtp_host', $_POST['smtp_host'] ?? '');
            set_setting('smtp_port', $_POST['smtp_port'] ?? '587');
            set_setting('smtp_user', $_POST['smtp_user'] ?? '');
            set_setting('smtp_pass', $_POST['smtp_pass'] ?? '');
            set_setting('smtp_enc', $_POST['smtp_enc'] ?? 'tls');
        }

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
$mail_driver = get_setting('mail_driver', 'mail');
$mail_sender_name = get_setting('mail_sender_name', get_setting('site_title', 'Tala Online'));
$mail_sender_email = get_setting('mail_sender_email', 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

$smtp_host = get_setting('smtp_host');
$smtp_port = get_setting('smtp_port', '587');
$smtp_user = get_setting('smtp_user');
$smtp_pass = get_setting('smtp_pass');
$smtp_enc = get_setting('smtp_enc', 'tls');

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
                    <label>متد ارسال ایمیل</label>
                    <div class="input-icon-wrapper">
                        <span class="icon"><i data-lucide="cpu" class="w-4 h-4"></i></span>
                        <select name="mail_driver" id="mail_driver" onchange="toggleSMTPFields()">
                            <option value="mail" <?= $mail_driver === 'mail' ? 'selected' : '' ?>>PHP mail() - پیش‌فرض</option>
                            <option value="smtp" <?= $mail_driver === 'smtp' ? 'selected' : '' ?>>SMTP - توصیه شده برای عدم اسپم</option>
                        </select>
                    </div>
                </div>
            </div>

            <div id="smtp_fields" class="<?= $mail_driver !== 'smtp' ? 'hidden' : '' ?> space-y-6 p-6 bg-slate-50 rounded-xl border border-slate-100 animate-fade-in">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="form-group">
                        <label>Host</label>
                        <input type="text" name="smtp_host" value="<?= htmlspecialchars($smtp_host) ?>" placeholder="smtp.gmail.com" class="ltr-input">
                    </div>
                    <div class="form-group">
                        <label>Port</label>
                        <input type="text" name="smtp_port" value="<?= htmlspecialchars($smtp_port) ?>" placeholder="587" class="ltr-input">
                    </div>
                    <div class="form-group">
                        <label>Encryption</label>
                        <select name="smtp_enc" class="ltr-input">
                            <option value="tls" <?= $smtp_enc === 'tls' ? 'selected' : '' ?>>TLS (توصیه شده)</option>
                            <option value="ssl" <?= $smtp_enc === 'ssl' ? 'selected' : '' ?>>SSL</option>
                            <option value="none" <?= $smtp_enc === 'none' ? 'selected' : '' ?>>None</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="smtp_user" value="<?= htmlspecialchars($smtp_user) ?>" class="ltr-input">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <div class="relative">
                            <input type="password" id="smtp_pass" name="smtp_pass" value="<?= htmlspecialchars($smtp_pass) ?>" class="ltr-input !pl-12">
                            <button type="button" onclick="togglePassword('smtp_pass', this)" class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 hover:text-indigo-600 transition-colors">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
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

    <!-- Deliverability & DNS Guidance -->
    <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
        <div class="px-8 py-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/30">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-emerald-600 border border-emerald-50">
                <i data-lucide="shield-check" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-lg font-black text-slate-800">راهنمای تحویل‌پذیری و DNS</h2>
                <p class="text-[10px] text-slate-400 font-bold uppercase ">Deliverability & DNS Records</p>
            </div>
        </div>
        <div class="p-8 space-y-6">
            <p class="text-sm text-slate-600 leading-relaxed">
                برای جلوگیری از اسپم شدن ایمیل‌ها، حتماً رکوردهای زیر را در تنظیمات DNS دامنه خود (مانند کلودفلر یا پنل هاست) اضافه کنید:
            </p>

            <div class="space-y-4">
                <div class="p-4 bg-slate-50 rounded-lg border border-slate-100">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-[10px] font-black rounded">SPF Record</span>
                        <span class="text-[10px] text-slate-400 font-bold">(TXT Record)</span>
                    </div>
                    <code class="text-xs font-mono break-all text-indigo-600">v=spf1 a mx ip4:<?= $_SERVER['SERVER_ADDR'] ?? 'SERVER_IP' ?> ~all</code>
                    <p class="text-[10px] text-slate-400 mt-2">این رکورد به سرورهای دریافت‌کننده (مانند Gmail) می‌گوید که سرور شما مجاز به ارسال ایمیل از طرف این دامنه است.</p>
                </div>

                <div class="p-4 bg-slate-50 rounded-lg border border-slate-100">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-[10px] font-black rounded">DMARC Record</span>
                        <span class="text-[10px] text-slate-400 font-bold">(TXT Record Name: _dmarc)</span>
                    </div>
                    <code class="text-xs font-mono break-all text-indigo-600">v=DMARC1; p=none;</code>
                    <p class="text-[10px] text-slate-400 mt-2">این رکورد به امنیت بیشتر و تایید هویت فرستنده کمک می‌کند.</p>
                </div>
            </div>

            <div class="p-4 bg-amber-50 rounded-lg border border-amber-100 flex items-start gap-3">
                <i data-lucide="info" class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5"></i>
                <div class="text-xs text-amber-800 leading-relaxed">
                    <strong class="block mb-1">نکته مهم:</strong>
                    اگر از متد <strong>PHP mail()</strong> استفاده می‌کنید، احتمال اسپم شدن همچنان وجود دارد. توصیه می‌شود یک اکانت ایمیل رسمی (مانند info@yourdomain.com) بسازید و از متد <strong>SMTP</strong> استفاده کنید.
                </div>
            </div>
        </div>
    </div>

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

<script>
function toggleSMTPFields() {
    const driver = document.getElementById('mail_driver').value;
    const smtpFields = document.getElementById('smtp_fields');
    if (driver === 'smtp') {
        smtpFields.classList.remove('hidden');
    } else {
        smtpFields.classList.add('hidden');
    }
}
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
