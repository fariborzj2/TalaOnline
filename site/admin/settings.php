<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
check_login();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $api_key = $_POST['api_key'];
    $api_sync_interval = $_POST['api_sync_interval'];
    $site_title = $_POST['site_title'];
    $site_description = $_POST['site_description'];
    $site_keywords = $_POST['site_keywords'];

    set_setting('api_key', $api_key);
    set_setting('api_sync_interval', $api_sync_interval);
    set_setting('site_title', $site_title);
    set_setting('site_description', $site_description);
    set_setting('site_keywords', $site_keywords);

    if (!empty($_POST['new_password'])) {
        $hashed_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_pass, $_SESSION['admin_id']]);
        $message .= 'تنظیمات و رمز عبور با موفقیت بروزرسانی شدند. ';
    } else {
        $message = 'تنظیمات با موفقیت ذخیره شد.';
    }
}

$api_key = get_setting('api_key');
$api_sync_interval = get_setting('api_sync_interval', '10');
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

<form method="POST" class="max-w-4xl space-y-8 pb-10">
    <!-- General Settings -->
    <div class="glass-card rounded-[20px] overflow-hidden border border-slate-200">
        <div class="px-8 py-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/30">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-indigo-600 border border-indigo-50">
                <i data-lucide="sliders" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-lg font-black text-slate-800">تنظیمات عمومی</h2>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Main Configuration</p>
            </div>
        </div>
        <div class="p-8 space-y-6">
            <div class="form-group">
                <label>عنوان اصلی وب‌سایت</label>
                <div class="relative">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-400">
                        <i data-lucide="layout" class="w-4 h-4"></i>
                    </span>
                    <input type="text" name="site_title" value="<?= htmlspecialchars($site_title) ?>" required class="pr-12">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label>کلید API نوسان (Navasan API Key)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-400">
                            <i data-lucide="key" class="w-4 h-4"></i>
                        </span>
                        <input type="text" name="api_key" value="<?= htmlspecialchars($api_key) ?>" placeholder="api_key_..." class="pr-12 font-mono text-sm">
                    </div>
                </div>
                <div class="form-group">
                    <label>فاصله زمانی بروزرسانی (دقیقه)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-400">
                            <i data-lucide="timer" class="w-4 h-4"></i>
                        </span>
                        <input type="number" name="api_sync_interval" value="<?= htmlspecialchars($api_sync_interval) ?>" min="1" required class="pr-12">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SEO Settings -->
    <div class="glass-card rounded-[20px] overflow-hidden border border-slate-200">
        <div class="px-8 py-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/30">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-emerald-600 border border-emerald-50">
                <i data-lucide="search" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-lg font-black text-slate-800">تنظیمات سئو (SEO)</h2>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Search Engine Optimization</p>
            </div>
        </div>
        <div class="p-8 space-y-6">
            <div class="form-group">
                <label>توضیحات متا (Meta Description)</label>
                <textarea name="site_description" rows="4" class="resize-none"><?= htmlspecialchars($site_description) ?></textarea>
                <p class="text-[10px] text-slate-400 mt-2 font-bold uppercase tracking-widest">توصیه شده: بین ۱۵۰ تا ۱۶۰ کاراکتر</p>
            </div>
            <div class="form-group">
                <label>کلمات کلیدی (Keywords)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-400">
                        <i data-lucide="hash" class="w-4 h-4"></i>
                    </span>
                    <input type="text" name="site_keywords" value="<?= htmlspecialchars($site_keywords) ?>" placeholder="طلا, سکه, ارز..." class="pr-12">
                </div>
                <p class="text-[10px] text-slate-400 mt-2 font-bold uppercase tracking-widest">کلمات را با کاما (,) از هم جدا کنید</p>
            </div>
        </div>
    </div>

    <!-- Security Settings -->
    <div class="glass-card rounded-[20px] overflow-hidden border border-slate-200">
        <div class="px-8 py-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/30">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-rose-600 border border-rose-50">
                <i data-lucide="shield-lock" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-lg font-black text-slate-800">امنیت و دسترسی</h2>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Security & Access</p>
            </div>
        </div>
        <div class="p-8">
            <div class="form-group mb-0">
                <label>تغییر رمز عبور مدیریت</label>
                <div class="relative">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-400">
                        <i data-lucide="lock" class="w-4 h-4"></i>
                    </span>
                    <input type="password" name="new_password" placeholder="در صورت عدم نیاز به تغییر، خالی بگذارید" class="pr-12">
                </div>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-end gap-4 pt-4">
        <button type="submit" class="btn-v3 btn-v3-primary w-full md:w-auto min-w-[200px]">
            <i data-lucide="save" class="w-5 h-5"></i>
            ذخیره تمامی تنظیمات سایت
        </button>
    </div>
</form>

<?php include __DIR__ . '/layout/footer.php'; ?>
