<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
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
$stmt = $pdo->query("SELECT p.*, i.name as persian_name FROM prices_cache p LEFT JOIN items i ON p.symbol = i.symbol ORDER BY p.updated_at DESC LIMIT 5");
$recent_updates = $stmt->fetchAll();

$page_title = 'داشبورد مدیریتی';
$page_subtitle = 'خلاصه وضعیت سیستم و آخرین نرخ‌های دریافتی از بازار';

$header_action = '<a href="sync.php" class="btn-v3 btn-v3-primary"><i data-lucide="refresh-cw" class="w-4 h-4"></i> بروزرسانی دستی</a>';

include __DIR__ . '/layout/header.php';
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
    <div class="glass-card p-6 rounded-xl relative overflow-hidden group">
        <div class="flex items-center gap-5 relative z-10">
            <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition-all duration-300">
                <i data-lucide="database" class="w-7 h-7"></i>
            </div>
            <div>
                <p class="text-xs font-black text-slate-400 uppercase ">تعداد کل آیتم‌ها</p>
                <h3 class="text-2xl font-black text-slate-900 mt-1"><?= fa_num(number_format($total_items)) ?></h3>
            </div>
        </div>
    </div>

    <div class="glass-card p-6 rounded-xl relative overflow-hidden group">
        <div class="flex items-center gap-5 relative z-10">
            <div class="w-14 h-14 bg-amber-50 text-amber-600 rounded-lg flex items-center justify-center group-hover:bg-amber-600 group-hover:text-white transition-all duration-300">
                <i data-lucide="layout-grid" class="w-7 h-7"></i>
            </div>
            <div>
                <p class="text-xs font-black text-slate-400 uppercase ">پلتفرم‌های فعال</p>
                <h3 class="text-2xl font-black text-slate-900 mt-1"><?= fa_num(number_format($total_platforms)) ?></h3>
            </div>
        </div>
    </div>

    <div class="glass-card p-6 rounded-xl relative overflow-hidden group">
        <div class="flex items-center gap-5 relative z-10">
            <?php $api_status = $usage ? 'success' : 'danger'; ?>
            <div class="w-14 h-14 bg-<?= $api_status == 'success' ? 'emerald' : 'rose' ?>-50 text-<?= $api_status == 'success' ? 'emerald' : 'rose' ?>-600 rounded-lg flex items-center justify-center group-hover:bg-<?= $api_status == 'success' ? 'emerald' : 'rose' ?>-600 group-hover:text-white transition-all duration-300">
                <i data-lucide="<?= $usage ? 'check-circle' : 'x-circle' ?>" class="w-7 h-7"></i>
            </div>
            <div>
                <p class="text-xs font-black text-slate-400 uppercase ">وضعیت اتصال API</p>
                <h3 class="text-xl font-black text-slate-900 mt-1"><?= $usage ? 'متصل و عملیاتی' : 'خطا در اتصال' ?></h3>
            </div>
        </div>
    </div>
</div>

<?php if ($usage): ?>
<div class="mb-10">
    <div class="flex items-center gap-3 mb-6">
        <h2 class="text-lg font-black text-slate-800">آمار مصرف وب‌سرویس نوسان</h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="glass-card p-6 rounded-xl border-b-4 border-indigo-500">
            <p class="text-xs font-black text-slate-400 uppercase ">درخواست‌های ماه جاری</p>
            <div class="flex items-end justify-between mt-2">
                <h3 class="text-2xl font-black text-slate-900"><?= fa_num(number_format($usage['monthly_usage'] ?? 0)) ?></h3>
                <span class="text-[10px] font-black bg-indigo-50 text-indigo-600 px-2 py-1 rounded-lg">REQ/MONTH</span>
            </div>
        </div>
        <div class="glass-card p-6 rounded-xl border-b-4 border-emerald-500">
            <p class="text-xs font-black text-slate-400 uppercase ">درخواست‌های امروز</p>
            <div class="flex items-end justify-between mt-2">
                <h3 class="text-2xl font-black text-slate-900"><?= fa_num(number_format($usage['daily_usage'] ?? 0)) ?></h3>
                <span class="text-[10px] font-black bg-emerald-50 text-emerald-600 px-2 py-1 rounded-lg">REQ/DAY</span>
            </div>
        </div>
        <div class="glass-card p-6 rounded-xl border-b-4 border-amber-500">
            <p class="text-xs font-black text-slate-400 uppercase ">درخواست‌های ساعت اخیر</p>
            <div class="flex items-end justify-between mt-2">
                <h3 class="text-2xl font-black text-slate-900"><?= fa_num(number_format($usage['hourly_usage'] ?? 0)) ?></h3>
                <span class="text-[10px] font-black bg-amber-50 text-amber-600 px-2 py-1 rounded-lg">REQ/HOUR</span>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="glass-card rounded-xl overflow-hidden border border-slate-200">
    <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/30">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-slate-400">
                <i data-lucide="history" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-lg font-black text-slate-800">بروزرسانی‌های اخیر قیمت</h2>
                <p class="text-xs text-slate-400 font-medium">نمایش ۵ فعالیت اخیر سیستم</p>
            </div>
        </div>
        <button class="text-indigo-600 text-sm font-black hover:underline">مشاهده همه</button>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full admin-table">
            <thead>
                <tr>
                    <th>نماد سیستم</th>
                    <th>قیمت لحظه‌ای</th>
                    <th>تغییرات (۲۴ساعت)</th>
                    <th>زمان بروزرسانی</th>
                    <th class="text-center">وضعیت</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($recent_updates as $update): ?>
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600 font-black text-[10px]">
                                <?= strtoupper(substr($update['symbol'], 0, 2)) ?>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-black text-slate-900 text-xs"><?= htmlspecialchars($update['persian_name'] ?? 'نامشخص') ?></span>
                                <span class="text-[10px] font-bold text-slate-400 ltr-input"><?= htmlspecialchars($update['symbol']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="flex items-baseline gap-1">
                            <span class="text-base font-black text-slate-900"><?= fa_num(number_format($update['price'])) ?></span>
                            <span class="text-[10px] font-bold text-slate-400">تومان</span>
                        </div>
                    </td>
                    <td>
                        <?php
                        $pct = (float)$update['change_percent'];
                        $change_val = (float)($update['change_val'] ?? 0);
                        $is_up = $pct >= 0;
                        ?>
                        <div class="flex flex-col">
                            <div class="flex items-center gap-1 <?= $is_up ? 'text-emerald-600' : 'text-rose-600' ?> font-black text-xs">
                                <i data-lucide="<?= $is_up ? 'trending-up' : 'trending-down' ?>" class="w-3 h-3"></i>
                                <span><?= ($pct > 0 ? '+' : '') . fa_num($pct) ?>%</span>
                            </div>
                            <span class="text-[10px] font-bold text-slate-400" dir="ltr">
                                <?= ($is_up ? '+ ' : '- ') . fa_num(number_format(abs($change_val))) ?>
                            </span>
                        </div>
                    </td>
                    <td class="text-slate-400 font-medium text-xs"><?= jalali_time_tag($update['updated_at'], 'time') ?></td>
                    <td class="text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-600 border border-emerald-100">
                            فعال
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
