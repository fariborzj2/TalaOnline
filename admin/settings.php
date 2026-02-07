<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';
check_login();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $api_key = $_POST['api_key'];
    $site_title = $_POST['site_title'];
    $site_description = $_POST['site_description'];
    $site_keywords = $_POST['site_keywords'];

    set_setting('api_key', $api_key);
    set_setting('site_title', $site_title);
    set_setting('site_description', $site_description);
    set_setting('site_keywords', $site_keywords);

    // Check if password change is requested
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
$site_title = get_setting('site_title', 'طلا آنلاین');
$site_description = get_setting('site_description', 'مرجع تخصصی قیمت لحظه‌ای طلا، سکه و ارز. مقایسه بهترین پلتفرم‌های خرید و فروش طلا در ایران.');
$site_keywords = get_setting('site_keywords', 'قیمت طلا, قیمت سکه, دلار تهران, خرید طلا, مقایسه قیمت طلا');

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تنظیمات سیستم - طلا آنلاین</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #e29b21; --bg: #f8fafc; --sidebar: #1e293b; --card: #ffffff; --text: #475569; --title: #1e293b; --border: #e2e8f0; }
        body { font-family: 'Vazirmatn', sans-serif; background-color: var(--bg); color: var(--text); margin: 0; display: flex; }
        .sidebar { width: 260px; background: var(--sidebar); color: white; min-height: 100vh; padding: 30px 20px; box-sizing: border-box; position: fixed; right: 0; top: 0; }
        .main-content { flex-grow: 1; margin-right: 260px; padding: 40px; }
        .nav-link { color: #cbd5e1; text-decoration: none; display: block; padding: 12px 15px; border-radius: 12px; transition: all 0.3s; }
        .nav-link:hover, .nav-link.active { background: rgba(226, 155, 33, 0.1); color: var(--primary); }
        .card { background: var(--card); border-radius: 20px; padding: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid var(--border); max-width: 600px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; }
        input[type="text"], input[type="password"] { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 12px; box-sizing: border-box; }
        .btn { padding: 12px 25px; border-radius: 12px; font-weight: 600; cursor: pointer; border: none; background: var(--primary); color: white; width: 100%; }
        .alert { padding: 15px; background: #dcfce7; color: #16a34a; border-radius: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div style="font-size: 1.5rem; color: var(--primary); margin-bottom: 40px; text-align: center;">TalaOnline Admin</div>
    <ul class="nav-menu">
        <li class="nav-item"><a href="index.php" class="nav-link">داشبورد</a></li>
        <li class="nav-item"><a href="items.php" class="nav-link">مدیریت آیتم‌ها</a></li>
        <li class="nav-item"><a href="platforms.php" class="nav-link">مدیریت پلتفرم‌ها</a></li>
        <li class="nav-item"><a href="settings.php" class="nav-link active">تنظیمات سیستم</a></li>
    </ul>
</div>

<div class="main-content">
    <h1>تنظیمات سیستم</h1>

    <?php if ($message): ?>
        <div class="alert"><?= $message ?></div>
    <?php endif; ?>

    <div class="card">
        <form method="POST">
            <div class="form-group">
                <label>عنوان سایت</label>
                <input type="text" name="site_title" value="<?= htmlspecialchars($site_title) ?>" required>
            </div>
            <div class="form-group">
                <label>توضیحات سئو (Meta Description)</label>
                <textarea name="site_description" style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 12px; box-sizing: border-box; font-family: inherit; height: 100px;"><?= htmlspecialchars($site_description) ?></textarea>
            </div>
            <div class="form-group">
                <label>کلمات کلیدی (جدا شده با کاما)</label>
                <input type="text" name="site_keywords" value="<?= htmlspecialchars($site_keywords) ?>">
            </div>
            <div class="form-group">
                <label>کلید API نوسان (Navasan API Key)</label>
                <input type="text" name="api_key" value="<?= htmlspecialchars($api_key) ?>" placeholder="api_key_...">
            </div>
            <hr style="margin: 30px 0; border: 0; border-top: 1px solid var(--border);">
            <h3>تغییر رمز عبور مدیریت</h3>
            <div class="form-group">
                <label>رمز عبور جدید (در صورت عدم نیاز خالی بگذارید)</label>
                <input type="password" name="new_password">
            </div>
            <button type="submit" class="btn">ذخیره تنظیمات</button>
        </form>
    </div>
</div>

</body>
</html>
