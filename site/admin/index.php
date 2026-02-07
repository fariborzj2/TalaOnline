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

$page_title = 'داشبورد مدیریت';
$page_subtitle = 'خلاصه وضعیت سیستم و نرخ‌های لحظه‌ای';

$header_action = '<a href="sync.php" class="btn btn-primary"><i data-lucide="refresh-cw"></i> بروزرسانی دستی</a>';

include __DIR__ . '/layout/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: #e0f2fe; color: #0ea5e9;">
            <i data-lucide="database"></i>
        </div>
        <div class="stat-info">
            <h3>تعداد کل آیتم‌ها</h3>
            <div class="value"><?= number_format($total_items) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #fef3c7; color: #d97706;">
            <i data-lucide="layout-grid"></i>
        </div>
        <div class="stat-info">
            <h3>پلتفرم‌های فعال</h3>
            <div class="value"><?= number_format($total_platforms) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: <?= $usage ? '#dcfce7' : '#fee2e2' ?>; color: <?= $usage ? '#16a34a' : '#dc2626' ?>;">
            <i data-lucide="<?= $usage ? 'check-circle' : 'x-circle' ?>"></i>
        </div>
        <div class="stat-info">
            <h3>وضعیت اتصال API</h3>
            <div class="value"><?= $usage ? 'فعال' : 'خطا در اتصال' ?></div>
        </div>
    </div>
</div>

<?php if ($usage): ?>
<h2 style="margin-bottom: 1.5rem; font-size: 1.1rem; color: var(--text-muted);">آمار مصرف وب‌سرویس نوسان</h2>
<div class="stats-grid">
    <div class="stat-card" style="border-bottom: 3px solid #3b82f6;">
        <div class="stat-info">
            <h3>درخواست‌های ماه جاری</h3>
            <div class="value"><?= number_format($usage['monthly_usage'] ?? 0) ?></div>
        </div>
    </div>
    <div class="stat-card" style="border-bottom: 3px solid #10b981;">
        <div class="stat-info">
            <h3>درخواست‌های امروز</h3>
            <div class="value"><?= number_format($usage['daily_usage'] ?? 0) ?></div>
        </div>
    </div>
    <div class="stat-card" style="border-bottom: 3px solid #e29b21;">
        <div class="stat-info">
            <h3>درخواست‌های ساعت اخیر</h3>
            <div class="value"><?= number_format($usage['hourly_usage'] ?? 0) ?></div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2>بروزرسانی‌های اخیر قیمت</h2>
        <i data-lucide="clock" class="text-muted"></i>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>نماد</th>
                    <th>قیمت لحظه‌ای</th>
                    <th>درصد تغییر</th>
                    <th>زمان بروزرسانی</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_updates as $update): ?>
                <tr>
                    <td><code style="background: #f1f5f9; padding: 4px 8px; border-radius: 6px;"><?= htmlspecialchars($update['symbol']) ?></code></td>
                    <td style="font-weight: 700;"><?= number_format($update['price']) ?> <small class="text-muted">تومان</small></td>
                    <td>
                        <?php
                        $pct = (float)$update['change_percent'];
                        $class = $pct >= 0 ? 'badge-success' : 'badge-danger';
                        ?>
                        <span class="badge <?= $class ?>"><?= ($pct > 0 ? '+' : '') . $pct ?>%</span>
                    </td>
                    <td class="text-muted" style="font-size: 0.85rem;"><?= $update['updated_at'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
