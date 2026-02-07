<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
check_login();

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

<form method="POST" class="max-w-4xl space-y-8 pb-10">
    <!-- General Settings -->
    <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
        <div class="px-8 py-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/30">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-indigo-600 border border-indigo-50">
                <i data-lucide="sliders" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-lg font-black text-slate-800">تنظیمات عمومی</h2>
                <p class="text-[10px] text-slate-400 font-bold uppercase ">Main Configuration</p>
            </div>
        </div>
        <div class="p-8 space-y-6">
            <div class="form-group">
                <label>عنوان اصلی وب‌سایت</label>
                <div class="input-icon-wrapper">
                    <span class="icon"><i data-lucide="layout" class="w-4 h-4"></i></span>
                    <input type="text" name="site_title" value="<?= htmlspecialchars($site_title) ?>" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label>کلید API نوسان (Navasan API Key)</label>
                    <div class="input-icon-wrapper">
                        <span class="icon"><i data-lucide="key" class="w-4 h-4"></i></span>
                        <input type="text" name="api_key" value="<?= htmlspecialchars($api_key) ?>" placeholder="api_key_..." class="ltr-input font-mono text-xs">
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
        </div>
    </div>

    <!-- SEO Settings -->
    <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
        <div class="px-8 py-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/30">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-emerald-600 border border-emerald-50">
                <i data-lucide="search" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-lg font-black text-slate-800">تنظیمات سئو (SEO)</h2>
                <p class="text-[10px] text-slate-400 font-bold uppercase ">Search Engine Optimization</p>
            </div>
        </div>
        <div class="p-8 space-y-6">
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

    <!-- Backup & Restore -->
    <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
        <div class="px-8 py-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/30">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-amber-600 border border-amber-50">
                <i data-lucide="database" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-lg font-black text-slate-800">نسخه پشتیبان و بازگردانی</h2>
                <p class="text-[10px] text-slate-400 font-bold uppercase ">Backup & Restore</p>
            </div>
        </div>
        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-4">
                <h4 class="font-black text-slate-700 text-sm">دریافت فایل پشتیبان</h4>
                <p class="text-xs text-slate-400 leading-relaxed">یک نسخه کامل از دیتابیس شامل تمامی ارزها، پلتفرم‌ها و تنظیمات را بصورت فایل SQL دانلود کنید.</p>
                <a href="backup_handler.php?action=export" class="btn-v3 btn-v3-outline w-full !justify-start gap-3">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    دانلود فایل پشتیبان (Export)
                </a>
            </div>
            <div class="space-y-4 border-r border-slate-100 pr-8">
                <h4 class="font-black text-slate-700 text-sm">بازگردانی دیتابیس</h4>
                <p class="text-xs text-slate-400 leading-relaxed">فایل پشتیبان قبلی خود را انتخاب کرده و دیتابیس را به حالت قبل بازگردانید. <span class="text-rose-500 font-bold">(تمامی داده‌های فعلی جایگزین خواهند شد)</span></p>
                <div onclick="document.getElementById('importFile').click()" class="file-input-custom !bg-slate-50 border-dashed border-2 hover:border-indigo-500 group">
                    <span id="fileNameDisplay" class="text-xs font-bold text-slate-400 group-hover:text-indigo-600">انتخاب فایل SQL...</span>
                    <i data-lucide="file-up" class="w-4 h-4 text-slate-400 group-hover:text-indigo-600"></i>
                </div>
                <button type="button" onclick="confirmImport()" class="btn-v3 btn-v3-primary w-full gap-3">
                    <i data-lucide="upload" class="w-4 h-4"></i>
                    شروع بازگردانی (Import)
                </button>
            </div>
        </div>
    </div>

    <!-- Security Settings -->
    <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
        <div class="px-8 py-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/30">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-rose-600 border border-rose-50">
                <i data-lucide="lock" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-lg font-black text-slate-800">امنیت و دسترسی</h2>
                <p class="text-[10px] text-slate-400 font-bold uppercase ">Security & Access</p>
            </div>
        </div>
        <div class="p-8">
            <div class="form-group mb-0">
                <label>تغییر رمز عبور مدیریت</label>
                <div class="input-icon-wrapper">
                    <span class="icon"><i data-lucide="lock" class="w-4 h-4"></i></span>
                    <input type="password" name="new_password" placeholder="در صورت عدم نیاز به تغییر، خالی بگذارید" class="ltr-input">
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

<!-- Hidden Import Form -->
<form id="importForm" action="backup_handler.php?action=import" method="POST" enctype="multipart/form-data" class="hidden">
    <input type="file" name="backup_file" id="importFile" accept=".sql" onchange="document.getElementById('fileNameDisplay').innerText = this.files[0].name">
</form>

<script>
    function confirmImport() {
        const fileInput = document.getElementById('importFile');
        if (!fileInput.files.length) {
            alert('لطفاً ابتدا فایل پشتیبان را انتخاب کنید.');
            return;
        }
        if (confirm('آیا از بازگردانی دیتابیس اطمینان دارید؟ تمامی اطلاعات فعلی حذف و اطلاعات فایل جایگزین خواهد شد.')) {
            document.getElementById('importForm').submit();
        }
    }
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
