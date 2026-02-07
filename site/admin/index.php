<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/navasan_service.php';
check_login();

$navasan = new NavasanService($pdo);
$usage = $navasan->getUsage();

// Handle some basic stats
$stmt = $pdo->query("SELECT COUNT(*) FROM items");
$total_items = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM platforms");
$total_platforms = $stmt->fetchColumn();

// Get recent price updates
$stmt = $pdo->query("SELECT * FROM prices_cache ORDER BY updated_at DESC LIMIT 5");
$recent_updates = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پنل مدیریت - طلا آنلاین</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #e29b21;
            --bg: #f8fafc;
            --sidebar: #1e293b;
            --card: #ffffff;
            --text: #475569;
            --title: #1e293b;
            --border: #e2e8f0;
        }
        body {
            font-family: 'Vazirmatn', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            display: flex;
        }
        .sidebar {
            width: 260px;
            background: var(--sidebar);
            color: white;
            min-height: 100vh;
            padding: 30px 20px;
            box-sizing: border-box;
            position: fixed;
            right: 0;
            top: 0;
        }
        .main-content {
            flex-grow: 1;
            margin-right: 260px;
            padding: 40px;
        }
        .sidebar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 40px;
            text-align: center;
        }
        .nav-menu {
            list-style: none;
            padding: 0;
        }
        .nav-item {
            margin-bottom: 10px;
        }
        .nav-link {
            color: #cbd5e1;
            text-decoration: none;
            display: block;
            padding: 12px 15px;
            border-radius: 12px;
            transition: all 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(226, 155, 33, 0.1);
            color: var(--primary);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: var(--card);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            border: 1px solid var(--border);
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--title);
        }
        .stat-label {
            color: var(--text);
            font-size: 0.9rem;
        }
        .card {
            background: var(--card);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            border: 1px solid var(--border);
            margin-bottom: 30px;
        }
        .card-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--title);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: right;
            padding: 15px;
            border-bottom: 1px solid var(--border);
        }
        th {
            color: var(--text);
            font-weight: 600;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.8rem;
        }
        .badge-success { background: #dcfce7; color: #16a34a; }
        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            border: none;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-outline { border: 1px solid var(--border); color: var(--text); }
        .btn:hover { opacity: 0.9; }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-right: 0; padding: 20px; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-brand">TalaOnline Admin</div>
    <ul class="nav-menu">
        <li class="nav-item"><a href="index.php" class="nav-link active">داشبورد</a></li>
        <li class="nav-item"><a href="items.php" class="nav-link">مدیریت آیتم‌ها</a></li>
        <li class="nav-item"><a href="platforms.php" class="nav-link">مدیریت پلتفرم‌ها</a></li>
        <li class="nav-item"><a href="settings.php" class="nav-link">تنظیمات سیستم</a></li>
        <li class="nav-item" style="margin-top: 50px;"><a href="logout.php" class="nav-link" style="color: #ef4444;">خروج</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="header">
        <h1>داشبورد مدیریت</h1>
        <div>خوش آمدید، <?= htmlspecialchars($_SESSION['admin_username']) ?></div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $total_items ?></div>
            <div class="stat-label">تعداد کل آیتم‌ها</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $total_platforms ?></div>
            <div class="stat-label">پلتفرم‌های فعال</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $usage ? 'فعال' : 'خطا / بدون کلید' ?></div>
            <div class="stat-label">وضعیت اتصال به API</div>
        </div>
    </div>

    <?php if ($usage): ?>
    <div class="stats-grid" style="margin-bottom: 40px;">
        <div class="stat-card" style="border-right: 4px solid #3b82f6;">
            <div class="stat-value"><?= number_format($usage['monthly_usage'] ?? 0) ?></div>
            <div class="stat-label">درخواست‌های ماه جاری</div>
        </div>
        <div class="stat-card" style="border-right: 4px solid #10b981;">
            <div class="stat-value"><?= number_format($usage['daily_usage'] ?? 0) ?></div>
            <div class="stat-label">درخواست‌های امروز</div>
        </div>
        <div class="stat-card" style="border-right: 4px solid #e29b21;">
            <div class="stat-value"><?= number_format($usage['hourly_usage'] ?? 0) ?></div>
            <div class="stat-label">درخواست‌های ساعت اخیر</div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">
            بروزرسانی‌های اخیر قیمت
            <a href="sync.php" class="btn btn-outline" style="font-size: 0.8rem;">بروزرسانی دستی</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>نماد</th>
                    <th>قیمت آخرین تغییر</th>
                    <th>درصد تغییر</th>
                    <th>زمان بروزرسانی</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_updates as $update): ?>
                <tr>
                    <td><?= htmlspecialchars($update['symbol']) ?></td>
                    <td><?= number_format($update['price']) ?></td>
                    <td><?= $update['change_percent'] ?>%</td>
                    <td><?= $update['updated_at'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
