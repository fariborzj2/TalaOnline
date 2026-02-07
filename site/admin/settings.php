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

$page_title = 'تنظیمات سیستم';
$page_subtitle = 'مدیریت کلیدهای API، تنظیمات سئو و امنیت پنل';

include __DIR__ . '/layout/header.php';
?>

<?php if ($message): ?>
    <div class="badge badge-success" style="padding: 1rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
        <i data-lucide="check-circle" style="width: 18px;"></i>
        <?= $message ?>
    </div>
<?php endif; ?>

<form method="POST" style="max-width: 800px;">
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h2 style="display: flex; align-items: center; gap: 0.5rem;"><i data-lucide="settings-2" style="width: 20px;"></i> تنظیمات عمومی</h2>
        </div>
        <div style="padding: 2rem;">
            <div class="form-group">
                <label>عنوان سایت</label>
                <input type="text" name="site_title" value="<?= htmlspecialchars($site_title) ?>" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
                <div class="form-group">
                    <label>کلید API نوسان (Navasan API Key)</label>
                    <input type="text" name="api_key" value="<?= htmlspecialchars($api_key) ?>" placeholder="api_key_...">
                </div>
                <div class="form-group">
                    <label>فاصله زمانی بروزرسانی (دقیقه)</label>
                    <input type="number" name="api_sync_interval" value="<?= htmlspecialchars($api_sync_interval) ?>" min="1" required>
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h2 style="display: flex; align-items: center; gap: 0.5rem;"><i data-lucide="search" style="width: 20px;"></i> تنظیمات سئو (SEO)</h2>
        </div>
        <div style="padding: 2rem;">
            <div class="form-group">
                <label>توضیحات متا (Meta Description)</label>
                <textarea name="site_description" rows="3"><?= htmlspecialchars($site_description) ?></textarea>
            </div>
            <div class="form-group">
                <label>کلمات کلیدی (Keywords)</label>
                <input type="text" name="site_keywords" value="<?= htmlspecialchars($site_keywords) ?>" placeholder="طلا, سکه, ارز...">
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h2 style="display: flex; align-items: center; gap: 0.5rem;"><i data-lucide="shield-check" style="width: 20px;"></i> امنیت</h2>
        </div>
        <div style="padding: 2rem;">
            <div class="form-group">
                <label>تغییر رمز عبور مدیریت (در صورت عدم نیاز خالی بگذارید)</label>
                <input type="password" name="new_password" placeholder="رمز عبور جدید">
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary" style="padding: 1rem 3rem; font-size: 1rem;">
        <i data-lucide="save"></i>
        ذخیره تمامی تنظیمات
    </button>
</form>

<?php include __DIR__ . '/layout/footer.php'; ?>
