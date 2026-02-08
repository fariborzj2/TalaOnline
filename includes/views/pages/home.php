<div id="error-banner" class="center mb-20 d-none">
    <div class="error-container block-card">
        <div class="error-icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        </div>
        <div class="error-content">
            <h3 class="error-title">خطا در دریافت اطلاعات</h3>
            <p class="error-message">متأسفانه در حال حاضر قادر به دریافت اطلاعات از سرور نیستیم. لطفا اتصال اینترنت خود را بررسی کنید.</p>
        </div>
        <button id="reload-btn" class="btn btn-error" aria-label="تلاش مجدد برای بارگذاری اطلاعات">تلاش مجدد</button>
    </div>
</div>

<div class="section">
    <div class="center">
        <div class="d-flex-wrap gap-20">
            <?= View::renderSection('summary', [
                'gold_data' => $gold_data,
                'silver_data' => $silver_data
            ]) ?>

            <?= View::renderSection('chart', [
                'gold_data' => $gold_data
            ]) ?>
        </div>
    </div>
</div>

<div class="section">
    <div class="center d-flex-wrap gap-20 just-center">
        <?= View::renderSection('platforms', [
            'platforms' => $platforms
        ]) ?>

        <?= View::renderSection('coins', [
            'coins' => $coins
        ]) ?>
    </div>
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
