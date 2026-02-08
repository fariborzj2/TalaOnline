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

<!-- Hero Section: Summary & Chart -->
<div class="flex flex-col lg:flex-row gap-8 mb-12">
    <?= View::renderSection('summary', [
        'gold_data' => $gold_data,
        'silver_data' => $silver_data
    ]) ?>

    <?= View::renderSection('chart', [
        'gold_data' => $gold_data
    ]) ?>
</div>

<!-- Platforms & Coins Section -->
<div class="flex flex-col lg:flex-row gap-8">
    <?= View::renderSection('platforms', [
        'platforms' => $platforms
    ]) ?>

    <?= View::renderSection('coins', [
        'coins' => $coins
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
