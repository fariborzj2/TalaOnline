<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/core/LSCache.php';
check_permission("settings.view");

$message = '';

if (isset($_GET['message'])) {
    if ($_GET['message'] === 'backup_imported') $message = 'نسخه پشتیبان با موفقیت بازگردانی شد.';
}
if (isset($_GET['error'])) {
    $error = 'خطا: ' . htmlspecialchars($_GET['error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $api_key = $_POST['api_key'];
    $api_sync_interval = $_POST['api_sync_interval'];
    $home_category_limit = $_POST['home_category_limit'];
    $site_title = $_POST['site_title'];
    $site_url = $_POST['site_url'];
    $mail_logo_url = $_POST['mail_logo_url'];
    $site_description = $_POST['site_description'];
    $site_keywords = $_POST['site_keywords'];
    $tinymce_api_key = $_POST['tinymce_api_key'];

    set_setting('site_url', $site_url);
    set_setting('mail_logo_url', $mail_logo_url);

    $social_telegram = $_POST['social_telegram'];
    $social_instagram = $_POST['social_instagram'];
    $social_twitter = $_POST['social_twitter'];
    $social_linkedin = $_POST['social_linkedin'];

    set_setting('social_telegram', $social_telegram);
    set_setting('social_instagram', $social_instagram);
    set_setting('social_twitter', $social_twitter);
    set_setting('social_linkedin', $social_linkedin);

    $google_login_enabled = isset($_POST['google_login_enabled']) ? '1' : '0';
    $google_client_id = $_POST['google_client_id'];
    $google_client_secret = $_POST['google_client_secret'];

    set_setting('api_key', $api_key);
    set_setting('api_sync_interval', $api_sync_interval);
    set_setting('home_category_limit', $home_category_limit);
    set_setting('site_title', $site_title);
    set_setting('site_description', $site_description);
    set_setting('site_keywords', $site_keywords);
    set_setting('tinymce_api_key', $tinymce_api_key);

    set_setting('google_login_enabled', $google_login_enabled);
    set_setting('google_client_id', $google_client_id);
    set_setting('google_client_secret', $google_client_secret);

    $mobile_verification_enabled = isset($_POST['mobile_verification_enabled']) ? '1' : '0';
    $kavenegar_api_key = $_POST['kavenegar_api_key'];
    $kavenegar_template = $_POST['kavenegar_template'];

    set_setting('mobile_verification_enabled', $mobile_verification_enabled);
    set_setting('kavenegar_api_key', $kavenegar_api_key);
    set_setting('kavenegar_template', $kavenegar_template);

    // Rate Limiting Settings
    set_setting('rate_limit_sms_max', $_POST['rate_limit_sms_max']);
    set_setting('rate_limit_sms_window', $_POST['rate_limit_sms_window']);
    set_setting('rate_limit_sms_lock', $_POST['rate_limit_sms_lock']);
    set_setting('rate_limit_email_max', $_POST['rate_limit_email_max']);
    set_setting('rate_limit_email_window', $_POST['rate_limit_email_window']);
    set_setting('rate_limit_email_lock', $_POST['rate_limit_email_lock']);
    set_setting('rate_limit_ip_max', $_POST['rate_limit_ip_max']);
    set_setting('rate_limit_ip_window', $_POST['rate_limit_ip_window']);
    set_setting('rate_limit_ip_lock', $_POST['rate_limit_ip_lock']);
    set_setting('rate_limit_progressive', isset($_POST['rate_limit_progressive']) ? '1' : '0');

    // LiteSpeed Cache Settings
    set_setting('lscache_enabled', isset($_POST['lscache_enabled']) ? '1' : '0');
    set_setting('lscache_ttl', $_POST['lscache_ttl']);
    set_setting('lscache_home_ttl', $_POST['lscache_home_ttl']);
    set_setting('lscache_blog_ttl', $_POST['lscache_blog_ttl']);
    set_setting('lscache_purge_on_update', isset($_POST['lscache_purge_on_update']) ? '1' : '0');
    set_setting('lscache_path', $_POST['lscache_path'] ?? '');

    $message = 'تنظیمات با موفقیت ذخیره شد.';
}

$api_key = get_setting('api_key');
$api_sync_interval = get_setting('api_sync_interval', '10');
$home_category_limit = get_setting('home_category_limit', '5');
$site_title = get_setting('site_title', 'طلا آنلاین');
$site_description = get_setting('site_description', 'مرجع تخصصی قیمت لحظه‌ای طلا، سکه و ارز. مقایسه بهترین پلتفرم‌های خرید و فروش طلا در ایران.');
$site_keywords = get_setting('site_keywords', 'قیمت طلا, قیمت سکه, دلار تهران, خرید طلا, مقایسه قیمت طلا');

$page_title = 'تنظیمات سیستمی';
$page_subtitle = 'مدیریت پیکربندی اصلی سایت، کلمات کلیدی، توضیحات سئو و امنیت دسترسی به پنل';

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

<?php if (isset($error) && $error): ?>
    <div class="mb-8 animate-bounce-in">
        <div class="bg-rose-50 border border-rose-100 rounded-xl p-4 flex items-center gap-3 text-rose-700">
            <div class="w-8 h-8 bg-rose-500 text-white rounded-lg flex items-center justify-center">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
            </div>
            <span class="font-bold"><?= $error ?></span>
        </div>
    </div>
<?php endif; ?>

<form method="POST" class="pb-10">
    <div class="flex flex-col lg:flex-row gap-8 items-start">
        <!-- Settings Sidebar -->
        <aside class="w-full lg:w-72 flex-shrink-0 lg:sticky top-24">
            <div class="glass-card rounded-2xl border border-slate-200/60 p-3 shadow-sm">
                <nav class="space-y-1" id="settings-tabs">
                    <button type="button" onclick="switchTab('general', this)" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all duration-200 bg-indigo-50 text-indigo-600 group active-tab">
                        <i data-lucide="sliders" class="w-5 h-5 transition-transform group-hover:scale-110"></i>
                        تنظیمات عمومی
                    </button>
                    <button type="button" onclick="switchTab('mobile', this)" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all duration-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700 group">
                        <i data-lucide="smartphone" class="w-5 h-5 transition-transform group-hover:scale-110"></i>
                        تایید شماره موبایل
                    </button>
                    <button type="button" onclick="switchTab('rate-limit', this)" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all duration-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700 group">
                        <i data-lucide="shield-alert" class="w-5 h-5 transition-transform group-hover:scale-110"></i>
                        محدودیت ارسال
                    </button>
                    <button type="button" onclick="switchTab('google', this)" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all duration-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700 group">
                        <i data-lucide="chrome" class="w-5 h-5 transition-transform group-hover:scale-110"></i>
                        ورود با گوگل
                    </button>
                    <button type="button" onclick="switchTab('social', this)" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all duration-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700 group">
                        <i data-lucide="share-2" class="w-5 h-5 transition-transform group-hover:scale-110"></i>
                        شبکه‌های اجتماعی
                    </button>
                    <button type="button" onclick="switchTab('seo', this)" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all duration-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700 group">
                        <i data-lucide="search" class="w-5 h-5 transition-transform group-hover:scale-110"></i>
                        تنظیمات سئو
                    </button>
                    <button type="button" onclick="switchTab('backup', this)" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all duration-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700 group">
                        <i data-lucide="database" class="w-5 h-5 transition-transform group-hover:scale-110"></i>
                        پشتیبان‌گیری
                    </button>
                    <button type="button" onclick="switchTab('lscache', this)" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all duration-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700 group">
                        <i data-lucide="zap" class="w-5 h-5 transition-transform group-hover:scale-110"></i>
                        LiteSpeed Cache
                    </button>
                </nav>

                <div class="mt-6 pt-6 border-t border-slate-100 px-2">
                    <button type="submit" class="btn-v3 btn-v3-primary w-full gap-2 shadow-lg shadow-indigo-100">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        ذخیره تغییرات
                    </button>
                </div>
            </div>
        </aside>

        <!-- Settings Content -->
        <div class="flex-1 space-y-8 min-w-0">
            <!-- General Settings -->
            <div id="tab-general" class="setting-section transition-all duration-300">
                <div class="glass-card rounded-2xl overflow-hidden border border-slate-200/60 shadow-sm">
                    <div class="px-6 py-5 md:px-8 md:py-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/30">
                        <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-indigo-600 border border-indigo-50 shadow-sm">
                            <i data-lucide="sliders" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h2 class="text-base md:text-lg font-black text-slate-800">تنظیمات عمومی</h2>
                            <p class="text-[10px] text-slate-400 font-bold uppercase ">Main Configuration</p>
                        </div>
                    </div>
                    <div class="p-6 md:p-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label>عنوان اصلی وب‌سایت</label>
                    <div class="input-icon-wrapper">
                        <span class="icon"><i data-lucide="layout" class="w-4 h-4"></i></span>
                        <input type="text" name="site_title" value="<?= htmlspecialchars($site_title) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>آدرس وب‌سایت (Site URL)</label>
                    <div class="input-icon-wrapper">
                        <span class="icon"><i data-lucide="link" class="w-4 h-4"></i></span>
                        <input type="text" name="site_url" value="<?= htmlspecialchars(get_setting('site_url', get_base_url())) ?>" placeholder="https://example.com" class="ltr-input" required>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>آدرس لوگو مخصوص ایمیل (توصیه شده PNG با عرض ۱۰۰ پیکسل)</label>
                <div class="input-icon-wrapper">
                    <span class="icon"><i data-lucide="image" class="w-4 h-4"></i></span>
                    <input type="text" name="mail_logo_url" value="<?= htmlspecialchars(get_setting('mail_logo_url')) ?>" placeholder="https://example.com/logo.png" class="ltr-input">
                </div>
                <p class="text-[10px] text-slate-400 mt-2 font-bold uppercase ">اگر خالی باشد، از لوگوی اصلی سایت استفاده می‌شود. برای سازگاری حداکثری در ایمیل‌ها، از فرمت PNG استفاده کنید.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label>کلید API نوسان (Navasan API Key)</label>
                    <div class="input-icon-wrapper">
                        <span class="icon"><i data-lucide="key" class="w-4 h-4"></i></span>
                        <input type="password" name="api_key" value="<?= htmlspecialchars($api_key) ?>" placeholder="api_key_..." class="ltr-input font-mono text-xs">
                    </div>
                </div>
                <div class="form-group">
                    <label>فاصله زمانی بروزرسانی (دقیقه)</label>
                    <div class="input-icon-wrapper">
                        <span class="icon"><i data-lucide="timer" class="w-4 h-4"></i></span>
                        <input type="number" name="api_sync_interval" value="<?= htmlspecialchars($api_sync_interval) ?>" min="1" required class="ltr-input">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label>تعداد دارایی‌ها در هر دسته‌بندی (صفحه اصلی)</label>
                    <div class="input-icon-wrapper">
                        <span class="icon"><i data-lucide="list-ordered" class="w-4 h-4"></i></span>
                        <input type="number" name="home_category_limit" value="<?= htmlspecialchars($home_category_limit) ?>" min="1" max="50" required class="ltr-input">
                    </div>
                    <p class="text-[10px] text-slate-400 mt-2 font-bold uppercase ">حداکثر تعداد آیتم‌هایی که در باکس‌های صفحه اصلی نمایش داده می‌شود</p>
                </div>
            </div>

            <div class="form-group">
                <label>کلید API ویرایشگر (TinyMCE API Key)</label>
                <div class="input-icon-wrapper">
                    <span class="icon"><i data-lucide="code" class="w-4 h-4"></i></span>
                        <input type="password" name="tinymce_api_key" value="<?= htmlspecialchars(get_setting('tinymce_api_key')) ?>" placeholder="no-api-key" class="ltr-input font-mono text-xs">
                </div>
            </div>
        </div>
    </div>

            </div>

            <!-- Mobile Verification Settings -->
            <div id="tab-mobile" class="setting-section hidden transition-all duration-300">
                <div class="glass-card rounded-2xl overflow-hidden border border-slate-200/60 shadow-sm">
                    <div class="px-6 py-5 md:px-8 md:py-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/30">
                        <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-orange-600 border border-orange-50 shadow-sm">
                            <i data-lucide="smartphone" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h2 class="text-base md:text-lg font-black text-slate-800">تنظیمات تایید شماره موبایل</h2>
                            <p class="text-[10px] text-slate-400 font-bold uppercase ">SMS Verification (Kavenegar)</p>
                        </div>
                    </div>
                    <div class="p-6 md:p-8 space-y-6">
            <div class="form-group flex items-center gap-4">
                <label class="mb-0">فعال‌سازی تایید شماره موبایل</label>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="mobile_verification_enabled" value="1" class="sr-only peer" <?= get_setting('mobile_verification_enabled') === '1' ? 'checked' : '' ?>>
                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[5px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600"></div>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label>Kavenegar API Key</label>
                    <div class="input-icon-wrapper">
                        <span class="icon"><i data-lucide="key" class="w-4 h-4"></i></span>
                        <input type="password" name="kavenegar_api_key" value="<?= htmlspecialchars(get_setting('kavenegar_api_key')) ?>" class="ltr-input font-mono text-xs" placeholder="Kavenegar API Key">
                    </div>
                </div>
                <div class="form-group">
                    <label>Template Name (الگوی پیامک)</label>
                    <div class="input-icon-wrapper">
                        <span class="icon"><i data-lucide="file-text" class="w-4 h-4"></i></span>
                        <input type="text" name="kavenegar_template" value="<?= htmlspecialchars(get_setting('kavenegar_template')) ?>" class="ltr-input font-mono text-xs" placeholder="verify">
                    </div>
                </div>
            </div>
            <p class="text-[10px] text-slate-400 font-bold uppercase ">توجه: برای ارسال کد تایید از متد Lookup کاوه نگار استفاده می‌شود. مطمئن شوید الگوی تعریف شده در پنل کاوه نگار دارای متغیر {token} باشد.</p>
        </div>
    </div>

            </div>

            <!-- Rate Limiting Settings -->
            <div id="tab-rate-limit" class="setting-section hidden transition-all duration-300">
                <div class="glass-card rounded-2xl overflow-hidden border border-slate-200/60 shadow-sm">
                    <div class="px-6 py-5 md:px-8 md:py-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/30">
                        <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-rose-600 border border-rose-50 shadow-sm">
                            <i data-lucide="shield-alert" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h2 class="text-base md:text-lg font-black text-slate-800">تنظیمات محدودیت ارسال (Rate Limiting)</h2>
                            <p class="text-[10px] text-slate-400 font-bold uppercase ">Security & Anti-Spam Measures</p>
                        </div>
                    </div>
                    <div class="p-6 md:p-8 space-y-8">
            <div class="form-group flex items-center gap-4">
                <label class="mb-0">فعال‌سازی قفل تصاعدی (Progressive Locking)</label>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="rate_limit_progressive" value="1" class="sr-only peer" <?= get_setting('rate_limit_progressive') === '1' ? 'checked' : '' ?>>
                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[5px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-600"></div>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4 border-t border-slate-50">
                <div class="col-span-full mb-2">
                    <h4 class="font-black text-slate-700 text-sm flex items-center gap-2">
                        <i data-lucide="smartphone" class="w-4 h-4 text-orange-500"></i>
                        محدودیت پیامک (SMS)
                    </h4>
                </div>
                <div class="form-group">
                    <label>تعداد مجاز درخواست</label>
                    <input type="number" name="rate_limit_sms_max" value="<?= htmlspecialchars(get_setting('rate_limit_sms_max', '5')) ?>" min="1" class="ltr-input">
                </div>
                <div class="form-group">
                    <label>بازه زمانی (دقیقه)</label>
                    <input type="number" name="rate_limit_sms_window" value="<?= htmlspecialchars(get_setting('rate_limit_sms_window', '15')) ?>" min="1" class="ltr-input">
                </div>
                <div class="form-group">
                    <label>مدت زمان قفل (دقیقه)</label>
                    <input type="number" name="rate_limit_sms_lock" value="<?= htmlspecialchars(get_setting('rate_limit_sms_lock', '60')) ?>" min="1" class="ltr-input">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-6 border-t border-slate-50">
                <div class="col-span-full mb-2">
                    <h4 class="font-black text-slate-700 text-sm flex items-center gap-2">
                        <i data-lucide="mail" class="w-4 h-4 text-blue-500"></i>
                        محدودیت ایمیل (Email)
                    </h4>
                </div>
                <div class="form-group">
                    <label>تعداد مجاز درخواست</label>
                    <input type="number" name="rate_limit_email_max" value="<?= htmlspecialchars(get_setting('rate_limit_email_max', '5')) ?>" min="1" class="ltr-input">
                </div>
                <div class="form-group">
                    <label>بازه زمانی (دقیقه)</label>
                    <input type="number" name="rate_limit_email_window" value="<?= htmlspecialchars(get_setting('rate_limit_email_window', '15')) ?>" min="1" class="ltr-input">
                </div>
                <div class="form-group">
                    <label>مدت زمان قفل (دقیقه)</label>
                    <input type="number" name="rate_limit_email_lock" value="<?= htmlspecialchars(get_setting('rate_limit_email_lock', '60')) ?>" min="1" class="ltr-input">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-6 border-t border-slate-50">
                <div class="col-span-full mb-2">
                    <h4 class="font-black text-slate-700 text-sm flex items-center gap-2">
                        <i data-lucide="network" class="w-4 h-4 text-slate-500"></i>
                        محدودیت بر اساس IP
                    </h4>
                </div>
                <div class="form-group">
                    <label>تعداد مجاز درخواست</label>
                    <input type="number" name="rate_limit_ip_max" value="<?= htmlspecialchars(get_setting('rate_limit_ip_max', '10')) ?>" min="1" class="ltr-input">
                </div>
                <div class="form-group">
                    <label>بازه زمانی (دقیقه)</label>
                    <input type="number" name="rate_limit_ip_window" value="<?= htmlspecialchars(get_setting('rate_limit_ip_window', '15')) ?>" min="1" class="ltr-input">
                </div>
                <div class="form-group">
                    <label>مدت زمان قفل (دقیقه)</label>
                    <input type="number" name="rate_limit_ip_lock" value="<?= htmlspecialchars(get_setting('rate_limit_ip_lock', '120')) ?>" min="1" class="ltr-input">
                </div>
            </div>

            <p class="text-[10px] text-slate-400 font-bold uppercase mt-4">
                <i data-lucide="info" class="w-3 h-3 inline-block align-middle me-1"></i>
                این محدودیت‌ها برای جلوگیری از حملات SMS Bombing و Email Bombing اعمال می‌شوند. سیستم به صورت همزمان IP، ایمیل و شماره موبایل را کنترل می‌کند.
            </p>
        </div>
    </div>

            </div>

            <!-- Google Login Settings -->
            <div id="tab-google" class="setting-section hidden transition-all duration-300">
                <div class="glass-card rounded-2xl overflow-hidden border border-slate-200/60 shadow-sm">
                    <div class="px-6 py-5 md:px-8 md:py-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/30">
                        <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-blue-600 border border-blue-50 shadow-sm">
                            <i data-lucide="chrome" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h2 class="text-base md:text-lg font-black text-slate-800">تنظیمات ورود با گوگل</h2>
                            <p class="text-[10px] text-slate-400 font-bold uppercase ">Google OAuth Configuration</p>
                        </div>
                    </div>
                    <div class="p-6 md:p-8 space-y-6">
            <div class="form-group flex items-center gap-4">
                <label class="mb-0">فعال‌سازی ورود با گوگل</label>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="google_login_enabled" value="1" class="sr-only peer" <?= get_setting('google_login_enabled') === '1' ? 'checked' : '' ?>>
                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[5px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label>Client ID</label>
                    <div class="input-icon-wrapper">
                        <span class="icon"><i data-lucide="user-check" class="w-4 h-4"></i></span>
                        <input type="text" name="google_client_id" value="<?= htmlspecialchars(get_setting('google_client_id')) ?>" class="ltr-input font-mono text-xs" placeholder="123456789-abc.apps.googleusercontent.com">
                    </div>
                </div>
                <div class="form-group">
                    <label>Client Secret</label>
                    <div class="relative">
                        <div class="input-icon-wrapper">
                            <span class="icon"><i data-lucide="key-round" class="w-4 h-4"></i></span>
                            <input type="password" id="google_client_secret" name="google_client_secret" value="<?= htmlspecialchars(get_setting('google_client_secret')) ?>" class="ltr-input font-mono text-xs !pl-12" placeholder="GOCSPX-...">
                        </div>
                        <button type="button" onclick="togglePassword('google_client_secret', this)" class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 hover:text-indigo-600 transition-colors">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Redirect URI (برای تنظیم در کنسول گوگل)</label>
                <div class="input-icon-wrapper bg-slate-50">
                    <span class="icon"><i data-lucide="link" class="w-4 h-4"></i></span>
                    <input type="text" value="<?= get_base_url() ?>/api/google_auth.php" readonly class="ltr-input font-mono text-xs bg-transparent border-none">
                </div>
            </div>
        </div>
    </div>

            </div>

            <!-- Social Media Settings -->
            <div id="tab-social" class="setting-section hidden transition-all duration-300">
                <div class="glass-card rounded-2xl overflow-hidden border border-slate-200/60 shadow-sm">
                    <div class="px-6 py-5 md:px-8 md:py-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/30">
                        <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-rose-600 border border-rose-50 shadow-sm">
                            <i data-lucide="share-2" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h2 class="text-base md:text-lg font-black text-slate-800">شبکه‌های اجتماعی</h2>
                            <p class="text-[10px] text-slate-400 font-bold uppercase ">Social Media Links</p>
                        </div>
                    </div>
                    <div class="p-6 md:p-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label>تلگرام (Telegram)</label>
                    <div class="input-icon-wrapper">
                        <span class="icon"><i data-lucide="send" class="w-4 h-4"></i></span>
                        <input type="text" name="social_telegram" value="<?= htmlspecialchars(get_setting('social_telegram')) ?>" placeholder="https://t.me/yourchannel" class="ltr-input">
                    </div>
                </div>
                <div class="form-group">
                    <label>اینستاگرام (Instagram)</label>
                    <div class="input-icon-wrapper">
                        <span class="icon"><i data-lucide="instagram" class="w-4 h-4"></i></span>
                        <input type="text" name="social_instagram" value="<?= htmlspecialchars(get_setting('social_instagram')) ?>" placeholder="https://instagram.com/yourpage" class="ltr-input">
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label>توییتر / X (Twitter)</label>
                    <div class="input-icon-wrapper">
                        <span class="icon"><i data-lucide="twitter" class="w-4 h-4"></i></span>
                        <input type="text" name="social_twitter" value="<?= htmlspecialchars(get_setting('social_twitter')) ?>" placeholder="https://x.com/yourprofile" class="ltr-input">
                    </div>
                </div>
                <div class="form-group">
                    <label>لینکدین (LinkedIn)</label>
                    <div class="input-icon-wrapper">
                        <span class="icon"><i data-lucide="linkedin" class="w-4 h-4"></i></span>
                        <input type="text" name="social_linkedin" value="<?= htmlspecialchars(get_setting('social_linkedin')) ?>" placeholder="https://linkedin.com/in/yourprofile" class="ltr-input">
                    </div>
                </div>
            </div>
        </div>
    </div>

            </div>

            <!-- SEO Settings -->
            <div id="tab-seo" class="setting-section hidden transition-all duration-300">
                <div class="glass-card rounded-2xl overflow-hidden border border-slate-200/60 shadow-sm">
                    <div class="px-6 py-5 md:px-8 md:py-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/30">
                        <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-emerald-600 border border-emerald-50 shadow-sm">
                            <i data-lucide="search" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h2 class="text-base md:text-lg font-black text-slate-800">تنظیمات سئو (SEO)</h2>
                            <p class="text-[10px] text-slate-400 font-bold uppercase ">Search Engine Optimization</p>
                        </div>
                    </div>
                    <div class="p-6 md:p-8 space-y-6">
            <div class="form-group">
                <label>توضیحات متا (Meta Description)</label>
                <textarea name="site_description" rows="4" class="resize-none"><?= htmlspecialchars($site_description) ?></textarea>
                <p class="text-[10px] text-slate-400 mt-2 font-bold uppercase ">توصیه شده: بین ۱۵۰ تا ۱۶۰ کاراکتر</p>
            </div>
            <div class="form-group">
                <label>کلمات کلیدی (Keywords)</label>
                <div class="input-icon-wrapper">
                    <span class="icon"><i data-lucide="hash" class="w-4 h-4"></i></span>
                    <input type="text" name="site_keywords" value="<?= htmlspecialchars($site_keywords) ?>" placeholder="طلا, سکه, ارز...">
                </div>
                <p class="text-[10px] text-slate-400 mt-2 font-bold uppercase ">کلمات را با کاما (,) از هم جدا کنید</p>
            </div>
        </div>
    </div>

            </div>

            <!-- Backup & Restore -->
            <div id="tab-backup" class="setting-section hidden transition-all duration-300">
                <div class="glass-card rounded-2xl overflow-hidden border border-slate-200/60 shadow-sm">
                    <div class="px-6 py-5 md:px-8 md:py-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/30">
                        <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-amber-600 border border-amber-50 shadow-sm">
                            <i data-lucide="database" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h2 class="text-base md:text-lg font-black text-slate-800">نسخه پشتیبان و بازگردانی</h2>
                            <p class="text-[10px] text-slate-400 font-bold uppercase ">Backup & Restore</p>
                        </div>
                    </div>
                    <div class="p-6 md:p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-4">
                <h4 class="font-black text-slate-700 text-sm">دریافت فایل پشتیبان</h4>
                <p class="text-xs text-slate-400 leading-relaxed">یک نسخه کامل از دیتابیس شامل تمامی ارزها، پلتفرم‌ها و تنظیمات را بصورت فایل SQL دانلود کنید.</p>
                <a href="backup_handler.php?action=export" class="btn-v3 btn-v3-outline w-full !justify-start gap-3">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    دانلود فایل پشتیبان (Export)
                </a>
            </div>
            <div class="space-y-4 border-t md:border-t-0 md:border-r border-slate-100 pt-8 md:pt-0 md:pr-8">
                <h4 class="font-black text-slate-700 text-sm">بازگردانی دیتابیس</h4>
                <p class="text-xs text-slate-400 leading-relaxed">فایل پشتیبان قبلی خود را انتخاب کرده و دیتابیس را به حالت قبل بازگردانید. <span class="text-rose-500 font-bold">(تمامی داده‌های فعلی جایگزین خواهند شد)</span></p>

                <div id="importInitial">
                    <div onclick="document.getElementById('importFile').click()" class="file-input-custom !bg-slate-50 border-dashed border-2 hover:border-indigo-500 group mb-4">
                        <span id="fileNameDisplay" class="text-xs font-bold text-slate-400 group-hover:text-indigo-600">انتخاب فایل SQL...</span>
                        <i data-lucide="file-up" class="w-4 h-4 text-slate-400 group-hover:text-indigo-600"></i>
                    </div>
                    <button type="button" onclick="startImport()" id="importBtn" class="btn-v3 btn-v3-primary w-full gap-3">
                        <i data-lucide="upload" class="w-4 h-4"></i>
                        شروع بازگردانی (Import)
                    </button>
                </div>

                <div id="importProgress" class="hidden space-y-3">
                    <div class="flex items-center justify-between text-[10px] font-black uppercase">
                        <span class="text-slate-400">در حال بازگردانی داده‌ها...</span>
                        <span id="progressPercent" class="text-indigo-600">0%</span>
                    </div>
                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div id="progressBar" class="h-full bg-indigo-600 transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <p id="progressStatus" class="text-[9px] text-slate-400 font-bold">آماده‌سازی برای پردازش...</p>
                </div>
            </div>
        </div>
    </div>
</div>

            <!-- LiteSpeed Cache Settings -->
            <div id="tab-lscache" class="setting-section hidden transition-all duration-300">
                <div class="glass-card rounded-2xl overflow-hidden border border-slate-200/60 shadow-sm">
                    <div class="px-6 py-5 md:px-8 md:py-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/30">
                        <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-amber-500 border border-amber-50 shadow-sm">
                            <i data-lucide="zap" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h2 class="text-base md:text-lg font-black text-slate-800">تنظیمات LiteSpeed Web Cache</h2>
                            <p class="text-[10px] text-slate-400 font-bold uppercase ">High-Performance Page Caching</p>
                        </div>
                    </div>
                    <div class="p-6 md:p-8 space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-6">
                                <div class="form-group flex items-center gap-4">
                                    <label class="mb-0">فعال‌سازی LiteSpeed Cache</label>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="lscache_enabled" value="1" class="sr-only peer" <?= get_setting('lscache_enabled') === '1' ? 'checked' : '' ?>>
                                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[5px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                                    </label>
                                </div>

                                <div class="form-group">
                                    <label>زمان کش پیش‌فرض (TTL - ثانیه)</label>
                                    <div class="input-icon-wrapper">
                                        <span class="icon"><i data-lucide="clock" class="w-4 h-4"></i></span>
                                        <input type="number" name="lscache_ttl" value="<?= htmlspecialchars(get_setting('lscache_ttl', '3600')) ?>" min="60" class="ltr-input">
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-2 font-bold">زمان نگهداری صفحات در حافظه کش (مثلاً ۳۶۰۰ برای یک ساعت)</p>
                                </div>

                                <div class="form-group flex items-center gap-4">
                                    <label class="mb-0">پاکسازی خودکار هنگام بروزرسانی</label>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="lscache_purge_on_update" value="1" class="sr-only peer" <?= get_setting('lscache_purge_on_update', '1') === '1' ? 'checked' : '' ?>>
                                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[5px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                    </label>
                                </div>

                                <div class="form-group">
                                    <label>مسیر ذخیره‌سازی کش (اختیاری)</label>
                                    <div class="input-icon-wrapper">
                                        <span class="icon"><i data-lucide="folder" class="w-4 h-4"></i></span>
                                        <input type="text" name="lscache_path" value="<?= htmlspecialchars(get_setting('lscache_path')) ?>" placeholder="/home/user/lscache" class="ltr-input font-mono text-xs">
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-2 font-bold uppercase">برای محاسبه دقیق حجم کش. در صورت خالی بودن، سیستم سعی در شناسایی خودکار دارد.</p>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <div class="form-group">
                                    <label>زمان کش صفحه اصلی (ثانیه)</label>
                                    <div class="input-icon-wrapper">
                                        <span class="icon"><i data-lucide="home" class="w-4 h-4"></i></span>
                                        <input type="number" name="lscache_home_ttl" value="<?= htmlspecialchars(get_setting('lscache_home_ttl', '600')) ?>" min="60" class="ltr-input">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>زمان کش صفحات وبلاگ (ثانیه)</label>
                                    <div class="input-icon-wrapper">
                                        <span class="icon"><i data-lucide="file-text" class="w-4 h-4"></i></span>
                                        <input type="number" name="lscache_blog_ttl" value="<?= htmlspecialchars(get_setting('lscache_blog_ttl', '3600')) ?>" min="60" class="ltr-input">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-8 border-t border-slate-100">
                            <h4 class="font-black text-slate-700 text-sm mb-4">عملیات پاکسازی حافظه کش</h4>
                            <div class="flex flex-wrap gap-4">
                                <button type="button" onclick="purgeCache('all')" class="btn-v3 btn-v3-outline border-rose-200 text-rose-600 hover:bg-rose-50 gap-2">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    پاکسازی کل حافظه کش (Purge All)
                                </button>
                                <div class="flex items-center gap-2 px-4 py-2 bg-slate-50 rounded-xl border border-slate-100">
                                    <span class="text-[10px] font-black text-slate-400 uppercase">حجم فعلی کش:</span>
                                    <span class="text-xs font-black text-indigo-600"><?= LSCache::formatSize(LSCache::getCacheSize()) ?></span>
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-4 leading-relaxed">
                                <i data-lucide="info" class="w-3 h-3 inline-block align-middle me-1"></i>
                                برای استفاده از این قابلیت، وب‌سرور شما باید LiteSpeed باشد و ماژول LSCache روی آن فعال باشد. این پلاگین فقط هدرهای لازم را برای وب‌سرور ارسال می‌کند.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
async function purgeCache(type) {
    try {
        const res = await fetch('../api/lscache.php?action=purge_' + type);
        const data = await res.json();
        if (data.success) {
            await showAlert('حافظه کش با موفقیت پاکسازی شد.', 'success');
            location.reload();
        } else {
            throw new Error(data.error || 'خطایی رخ داد');
        }
    } catch (error) {
        await showAlert('خطا: ' + error.message, 'error');
    }
}

function switchTab(tabId, btn) {
    // Hide all sections
    document.querySelectorAll('.setting-section').forEach(section => {
        section.classList.add('hidden');
    });

    // Show selected section
    const targetSection = document.getElementById('tab-' + tabId);
    if (targetSection) {
        targetSection.classList.remove('hidden');
    }

    // Update active state in sidebar
    document.querySelectorAll('#settings-tabs button').forEach(button => {
        button.classList.remove('bg-indigo-50', 'text-indigo-600', 'active-tab');
        button.classList.add('text-slate-500');
    });

    if (btn) {
        btn.classList.add('bg-indigo-50', 'text-indigo-600', 'active-tab');
        btn.classList.remove('text-slate-500');
    }

    // Save last active tab to localStorage
    localStorage.setItem('admin_settings_active_tab', tabId);
}

// Initialize from localStorage or default
document.addEventListener('DOMContentLoaded', () => {
    const lastTab = localStorage.getItem('admin_settings_active_tab') || 'general';
    const buttons = document.querySelectorAll('#settings-tabs button');
    let targetBtn = null;

    buttons.forEach(btn => {
        const onclick = btn.getAttribute('onclick');
        if (onclick && onclick.includes("'" + lastTab + "'")) {
            targetBtn = btn;
        }
    });

    if (targetBtn) {
        switchTab(lastTab, targetBtn);
    } else {
        switchTab('general', buttons[0]);
    }
});
</script>

<!-- Hidden Import Input -->
<input type="file" id="importFile" accept=".sql" class="hidden" onchange="document.getElementById('fileNameDisplay').innerText = this.files[0].name">

<script>
    async function startImport() {
        const fileInput = document.getElementById('importFile');
        if (!fileInput.files.length) {
            await showAlert('لطفاً ابتدا فایل پشتیبان را انتخاب کنید.', 'warning');
            return;
        }

        const confirmed = await showConfirm('آیا از بازگردانی دیتابیس اطمینان دارید؟ تمامی اطلاعات فعلی حذف و اطلاعات فایل جایگزین خواهد شد.');
        if (!confirmed) {
            return;
        }

        const initialUI = document.getElementById('importInitial');
        const progressUI = document.getElementById('importProgress');
        const progressBar = document.getElementById('progressBar');
        const progressPercent = document.getElementById('progressPercent');
        const progressStatus = document.getElementById('progressStatus');

        initialUI.classList.add('hidden');
        progressUI.classList.remove('hidden');

        try {
            // Step 1: Init Import
            const formData = new FormData();
            formData.append('backup_file', fileInput.files[0]);

            progressStatus.innerText = 'در حال آپلود و تحلیل فایل...';
            const initRes = await fetch('backup_handler.php?action=init_import', {
                method: 'POST',
                body: formData
            });
            const initData = await initRes.json();

            if (!initData.success) throw new Error(initData.error);

            // Step 2: Execute Steps
            let done = false;
            while (!done) {
                const stepFormData = new FormData();
                const stepRes = await fetch('backup_handler.php?action=execute_step', {
                    method: 'POST',
                    body: stepFormData
                });
                const stepData = await stepRes.json();

                if (!stepData.success) throw new Error(stepData.error);

                const percent = Math.round((stepData.current / stepData.total) * 100);
                progressBar.style.width = percent + '%';
                progressPercent.innerText = percent + '%';
                progressStatus.innerText = `در حال اجرا: ${stepData.current} از ${stepData.total} دستور`;

                done = stepData.done;
            }

            progressStatus.innerText = 'بازگردانی با موفقیت انجام شد. در حال انتقال...';
            setTimeout(() => {
                window.location.href = 'settings.php?message=backup_imported';
            }, 1000);

        } catch (error) {
            await showAlert('خطا در بازگردانی: ' + error.message, 'error');
            initialUI.classList.remove('hidden');
            progressUI.classList.add('hidden');
        }
    }
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
