<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/mail.php';
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
            set_setting('smtp_skip_ssl_verify', isset($_POST['smtp_skip_ssl_verify']) ? '1' : '0');

            // DKIM Settings
            set_setting('dkim_domain', $_POST['dkim_domain'] ?? '');
            set_setting('dkim_selector', $_POST['dkim_selector'] ?? '');
            set_setting('dkim_private', $_POST['dkim_private'] ?? '');
        }

        set_setting('site_address', $_POST['site_address'] ?? '');

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
$smtp_skip_ssl_verify = get_setting('smtp_skip_ssl_verify', '0');

$dkim_domain = get_setting('dkim_domain');
$dkim_selector = get_setting('dkim_selector');
$dkim_private = get_setting('dkim_private');
$site_address = get_setting('site_address');

$templates = $pdo->query("SELECT * FROM email_templates ORDER BY id ASC")->fetchAll();

// Get queue status
$queue_pending = $pdo->query("SELECT COUNT(*) FROM email_queue WHERE status = 'pending'")->fetchColumn();
$queue_sent = $pdo->query("SELECT COUNT(*) FROM email_queue WHERE status = 'sent'")->fetchColumn();
$queue_failed = $pdo->query("SELECT COUNT(*) FROM email_queue WHERE status = 'failed' OR attempts >= 3")->fetchColumn();

$page_title = 'تنظیمات ایمیل';
$page_subtitle = 'مدیریت پیکربندی ارسال ایمیل و ویرایش قالب‌های اطلاع‌رسانی سیستم';

include __DIR__ . '/layout/header.php';

$phpmailer_exists = class_exists('PHPMailer\PHPMailer\PHPMailer');
?>

<?php if (!$phpmailer_exists): ?>
    <div class="mb-8 animate-bounce-in">
        <div class="bg-amber-50 border border-amber-100 rounded-xl p-6 flex flex-col gap-4 text-amber-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-500 text-white rounded-xl flex items-center justify-center shadow-lg shadow-amber-200">
                    <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="font-black text-lg">کتابخانه PHPMailer یافت نشد!</h3>
                    <p class="text-xs opacity-80 font-bold">PHPMailer Library Not Found</p>
                </div>
            </div>
            <p class="text-sm leading-relaxed">
                سیستم قادر به پیدا کردن PHPMailer نیست. این یعنی قابلیت ارسال از طریق <strong>SMTP</strong> در دسترس نخواهد بود. لطفاً برای فعال‌سازی ارسال ایمیل، کتابخانه مورد نیاز را نصب کنید.
            </p>
            <div class="p-4 bg-white/50 rounded-lg border border-amber-200">
                <p class="text-xs font-bold mb-2">راه‌حل:</p>
                <code class="text-xs font-mono block p-2 bg-slate-900 text-slate-300 rounded overflow-x-auto">composer install</code>
                <p class="text-[10px] mt-2 opacity-70">دستور بالا را در پوشه اصلی پروژه اجرا کنید یا محتویات پوشه vendor را آپلود کنید.</p>
            </div>
        </div>
    </div>
<?php endif; ?>

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
                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[5px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label>متد ارسال ایمیل</label>
                    <div class="input-icon-wrapper">
                        <span class="icon"><i data-lucide="cpu" class="w-4 h-4"></i></span>
                        <select name="mail_driver" id="mail_driver" disabled>
                            <option value="smtp" selected>SMTP - پروتکل ارسال استاندارد</option>
                        </select>
                        <input type="hidden" name="mail_driver" value="smtp">
                    </div>
                    <p class="text-[10px] text-indigo-600 font-bold mt-2">توجه: ارسال ایمیل در این سیستم منحصراً از طریق SMTP انجام می‌شود.</p>
                </div>
            </div>

            <div id="smtp_fields" class="space-y-6 p-6 bg-slate-50 rounded-xl border border-slate-100 animate-fade-in">
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
                <div class="form-group flex items-center gap-3 mt-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="smtp_skip_ssl_verify" value="1" class="sr-only peer" <?= $smtp_skip_ssl_verify === '1' ? 'checked' : '' ?>>
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[5px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                    </label>
                    <div>
                        <span class="text-sm font-bold text-slate-700">نادیده گرفتن خطای گواهی SSL (توصیه شده در صورت Timeout)</span>
                        <p class="text-[10px] text-slate-400">اگر در زمان اتصال TLS خطای تایم‌اوت دریافت می‌کنید، این گزینه را فعال کنید.</p>
                    </div>
                </div>

                <div class="pt-6 border-t border-slate-200 mt-6">
                    <h3 class="text-sm font-black text-slate-800 mb-4 flex items-center gap-2">
                        <i data-lucide="key" class="w-4 h-4 text-amber-500"></i>
                        تنظیمات امضای دیجیتال (DKIM)
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="form-group">
                            <label>DKIM Domain</label>
                            <input type="text" name="dkim_domain" value="<?= htmlspecialchars($dkim_domain) ?>" placeholder="example.com" class="ltr-input">
                        </div>
                        <div class="form-group">
                            <label>DKIM Selector</label>
                            <input type="text" name="dkim_selector" value="<?= htmlspecialchars($dkim_selector) ?>" placeholder="default" class="ltr-input">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>DKIM Private Key</label>
                        <textarea name="dkim_private" rows="5" class="ltr-input font-mono text-[10px]" placeholder="-----BEGIN RSA PRIVATE KEY----- ..."><?= htmlspecialchars($dkim_private) ?></textarea>
                        <p class="text-[10px] text-amber-600 font-bold mt-2">توجه: امضای DKIM باعث می‌شود ایمیل‌های شما توسط جیمیل و یاهو با اطمینان بیشتری پذیرفته شوند.</p>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>آدرس فیزیکی مجموعه (نمایش در پاورقی ایمیل)</label>
                <div class="input-icon-wrapper">
                    <span class="icon"><i data-lucide="map-pin" class="w-4 h-4"></i></span>
                    <input type="text" name="site_address" value="<?= htmlspecialchars($site_address) ?>" placeholder="مثال: تهران، خیابان ولیعصر، پلاک ۱۲۳">
                </div>
                <p class="text-[10px] text-slate-400 mt-1">درج آدرس فیزیکی در ایمیل‌ها یکی از الزامات قوانین ضد اسپم (CAN-SPAM) است و به افزایش اعتبار ایمیل‌های شما کمک می‌کند.</p>
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

            <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-slate-100">
                <button type="button" onclick="testSMTP()" class="btn-v3 btn-v3-outline text-indigo-600 border-indigo-200 hover:bg-indigo-50">
                    <i data-lucide="flask-conical" class="w-4 h-4"></i>
                    تست اتصال SMTP
                </button>
                <button type="submit" name="save_general" class="btn-v3 btn-v3-primary">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    ذخیره تنظیمات عمومی
                </button>
            </div>

            <div id="test_results" class="hidden mt-6 animate-fade-in">
                <div class="p-4 rounded-xl border flex flex-col gap-3" id="test_status_box">
                    <div class="flex items-center gap-3">
                        <div id="test_status_icon" class="w-8 h-8 rounded-full flex items-center justify-center"></div>
                        <span id="test_status_msg" class="font-bold text-sm"></span>
                    </div>
                    <div id="test_debug_container" class="hidden mt-2">
                        <p class="text-[10px] text-slate-400 font-bold uppercase mb-2">خروجی دیباگ:</p>
                        <pre id="test_debug_log" class="text-[10px] bg-slate-900 text-slate-300 p-4 rounded-lg overflow-x-auto font-mono ltr text-left leading-relaxed max-h-60"></pre>
                    </div>
                </div>
            </div>
        </div>
    </form>


    <!-- Queue Management -->
    <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
        <div class="px-8 py-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/30">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-rose-600 border border-rose-50">
                <i data-lucide="layers" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-lg font-black text-slate-800">مدیریت صف ارسال</h2>
                <p class="text-[10px] text-slate-400 font-bold uppercase ">Email Queue Status</p>
            </div>
        </div>
        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="p-4 bg-amber-50 border border-amber-100 rounded-xl text-center">
                    <p class="text-[10px] text-amber-600 font-black uppercase mb-1">در انتظار ارسال</p>
                    <span class="text-2xl font-black text-amber-700"><?= $queue_pending ?></span>
                </div>
                <div class="p-4 bg-emerald-50 border border-emerald-100 rounded-xl text-center">
                    <p class="text-[10px] text-emerald-600 font-black uppercase mb-1">ارسال شده</p>
                    <span class="text-2xl font-black text-emerald-700"><?= $queue_sent ?></span>
                </div>
                <div class="p-4 bg-rose-50 border border-rose-100 rounded-xl text-center">
                    <p class="text-[10px] text-rose-600 font-black uppercase mb-1">ناموفق</p>
                    <span class="text-2xl font-black text-rose-700"><?= $queue_failed ?></span>
                </div>
            </div>

            <div class="bg-slate-50 p-6 rounded-xl border border-slate-100 space-y-4">
                <h3 class="font-bold text-slate-800 text-sm">نحوه پردازش صف:</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    برای ارسال ایمیل‌های موجود در صف، باید فایل <code>site/api/mail_worker.php</code> به صورت دوره‌ای اجرا شود.
                </p>
                <div class="flex flex-col md:flex-row gap-4">
                    <a href="../api/mail_worker.php" target="_blank" class="btn-v3 btn-v3-outline text-indigo-600 border-indigo-200 hover:bg-indigo-50 text-xs">
                        <i data-lucide="play" class="w-4 h-4"></i>
                        اجرای دستی پردازشگر صف
                    </a>
                </div>
                <div class="mt-4 p-4 bg-white border border-slate-200 rounded-lg">
                    <p class="text-[10px] text-slate-400 font-black uppercase mb-2">دستور پیشنهادی Cron Job (هر ۵ دقیقه):</p>
                    <code class="text-[10px] font-mono text-indigo-600">*/5 * * * * php <?= realpath(__DIR__ . '/../api/mail_worker.php') ?> > /dev/null 2>&1</code>
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

                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="testTemplate('<?= $template['slug'] ?>')" class="btn-v3 btn-v3-outline text-indigo-600 border-indigo-200 hover:bg-indigo-50">
                            <i data-lucide="send" class="w-4 h-4"></i>
                            ارسال ایمیل تست
                        </button>
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

async function testTemplate(slug) {
    const testEmail = await request_user_input('لطفاً آدرس ایمیل مقصد برای تست این قالب را وارد کنید:');
    if (!testEmail) return;

    const csrfToken = document.querySelector('input[name="csrf_token"]').value;

    // Show loading state (could use a global loader or alert)
    const res = await fetch('../api/mail_test.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            test_email: testEmail,
            template_slug: slug,
            type: 'transactional',
            csrf_token: csrfToken
        })
    });
    const result = await res.json();

    if (result.success) {
        alert('ایمیل تست با موفقیت ارسال شد.');
    } else {
        alert('خطا در ارسال ایمیل تست: ' + (result.message || 'Unknown error'));
    }
}

async function testSMTP() {
    const testEmail = await request_user_input('لطفاً آدرس ایمیل مقصد برای تست را وارد کنید:');
    if (!testEmail) return;

    const data = {
        test_email: testEmail,
        smtp_host: document.querySelector('input[name="smtp_host"]').value,
        smtp_port: document.querySelector('input[name="smtp_port"]').value,
        smtp_user: document.querySelector('input[name="smtp_user"]').value,
        smtp_pass: document.querySelector('input[name="smtp_pass"]').value,
        smtp_enc: document.querySelector('select[name="smtp_enc"]').value,
        smtp_skip_ssl_verify: document.querySelector('input[name="smtp_skip_ssl_verify"]').checked ? '1' : '0',
        mail_sender_name: document.querySelector('input[name="mail_sender_name"]').value,
        mail_sender_email: document.querySelector('input[name="mail_sender_email"]').value,
        dkim_domain: document.querySelector('input[name="dkim_domain"]').value,
        dkim_selector: document.querySelector('input[name="dkim_selector"]').value,
        dkim_private: document.querySelector('textarea[name="dkim_private"]').value,
        type: 'transactional',
        csrf_token: document.querySelector('input[name="csrf_token"]').value
    };

    const resultsDiv = document.getElementById('test_results');
    const statusBox = document.getElementById('test_status_box');
    const statusIcon = document.getElementById('test_status_icon');
    const statusMsg = document.getElementById('test_status_msg');
    const debugContainer = document.getElementById('test_debug_container');
    const debugLog = document.getElementById('test_debug_log');

    resultsDiv.classList.remove('hidden');
    statusBox.className = 'p-4 rounded-xl border flex flex-col gap-3 bg-slate-50 border-slate-200';
    statusIcon.innerHTML = '<div class="w-4 h-4 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>';
    statusMsg.innerText = 'در حال برقراری ارتباط با سرور SMTP... (لطفاً منتظر بمانید)';
    debugContainer.classList.add('hidden');

    try {
        const res = await fetch('../api/mail_test.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();

        if (result.success) {
            statusBox.className = 'p-4 rounded-xl border flex flex-col gap-3 bg-emerald-50 border-emerald-100 text-emerald-700';
            statusIcon.innerHTML = '<i data-lucide="check" class="w-4 h-4 text-emerald-600"></i>';
            statusMsg.innerText = result.message;
        } else {
            statusBox.className = 'p-4 rounded-xl border flex flex-col gap-3 bg-rose-50 border-rose-100 text-rose-700';
            statusIcon.innerHTML = '<i data-lucide="x" class="w-4 h-4 text-rose-600"></i>';
            statusMsg.innerText = result.message;
        }

        if (result.debug) {
            debugContainer.classList.remove('hidden');
            debugLog.innerText = result.debug;
        }

        lucide.createIcons();
    } catch (e) {
        statusBox.className = 'p-4 rounded-xl border flex flex-col gap-3 bg-rose-50 border-rose-100 text-rose-700';
        statusIcon.innerHTML = '<i data-lucide="alert-triangle" class="w-4 h-4 text-rose-600"></i>';
        statusMsg.innerText = 'خطا در اجرای تست: ' + e.message;
        lucide.createIcons();
    }
}

async function request_user_input(message) {
    return new Promise((resolve) => {
        const email = prompt(message);
        resolve(email);
    });
}
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
