<!-- Error Banner -->
<div id="error-banner" class="max-w-7xl mx-auto px-4 mb-8 hidden">
    <div class="glass-card !bg-rose-50/80 dark:!bg-rose-900/20 border-rose-200 dark:border-rose-800 flex flex-col sm:flex-row items-center gap-6">
        <div class="w-16 h-16 rounded-2xl bg-rose-100 dark:bg-rose-900/30 text-rose-600 flex items-center justify-center shrink-0">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        </div>
        <div class="flex-grow text-center sm:text-right">
            <h3 class="text-lg font-black text-rose-800 dark:text-rose-200">خطا در دریافت اطلاعات</h3>
            <p class="text-sm text-rose-600 dark:text-rose-400 mt-1">متأسفانه در حال حاضر قادر به دریافت اطلاعات از سرور نیستیم.</p>
        </div>
        <button id="reload-btn" class="btn-primary !bg-rose-600 !shadow-rose-600/30 hover:!bg-rose-700">تلاش مجدد</button>
    </div>
</div>

<!-- Page Title & Actions -->
<div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-6">
    <h2 class="text-3xl font-black text-slate-900 dark:text-white">نمای بازار طلا و ارز</h2>

    <div class="flex items-center gap-4">
        <button class="flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 text-sm font-bold shadow-soft">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            <span><?= fa_num(date('Y/m/d')) ?></span>
        </button>
        <button class="flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 text-sm font-bold shadow-soft">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            <span>خروجی</span>
        </button>
    </div>
</div>

<!-- Summary Cards Row -->
<div class="mb-12">
    <?= View::renderSection('summary', [
        'gold_data' => $gold_data,
        'silver_data' => $silver_data,
        'coins' => array_slice($coins, 0, 3) // Add some coins to summary for variety
    ]) ?>
</div>

<!-- Performance Market Table -->
<div class="mb-12">
    <?= View::renderSection('market_performance', [
        'platforms' => $platforms,
        'coins' => $coins
    ]) ?>
</div>

<!-- Detailed Charts (Optional, as the image shows sparklines in table) -->
<div class="mb-12">
     <?= View::renderSection('chart', [
        'gold_data' => $gold_data
    ]) ?>
</div>

<script>
    window.__INITIAL_STATE__ = {
        platforms: <?= json_encode($platforms) ?>,
        coins: <?= json_encode($coins) ?>,
        summary: {
            gold: <?= json_encode($gold_data) ?>,
            silver: <?= json_encode($silver_data) ?>
        }
    };
</script>
