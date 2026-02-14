<div class="bg-block pd-md border grow-1 radius-16">
    <div class="d-flex-wrap just-between align-center mb-20 gap-1 mb-1">
        <div class="d-flex gap-1 align-center">
            <div class="w-12 h-12 radius-10 border d-flex just-center align-center">
                <i data-lucide="trending-up" class="icon-size-6"></i>
            </div>
            <div>
                <h2 class="text-title chart-section-title"><?= $title ?? 'نمودار قیمت طلا' ?></h2>
                <span class="font-size-0-9 chart-section-desc"><?= $desc ?? 'نوسانات قیمت طلا در بازه‌های زمانی مختلف' ?></span>
            </div>
        </div>
        <div class="d-flex d-column align-end gap-1 chart-controls">
            <?php if (!empty($chart_items)): ?>
            <div class="pill-toggle-group" id="chart-assets-toggle" <?= count($chart_items) <= 1 ? 'style="display: none;"' : '' ?>>
                <?php foreach ($chart_items as $index => $item): ?>
                    <button class="pill-btn chart-toggle-btn <?= $index === 0 ? 'active' : '' ?>"
                            data-symbol="<?= htmlspecialchars($item['symbol']) ?>"
                            data-name="<?= htmlspecialchars($item['name']) ?>">
                            <?= htmlspecialchars($item['name']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="pill-toggle-group period-toggle">
                <button class="pill-btn mode-btn active" id="period-7d" data-period="7d">۷ روزه</button>
                <button class="pill-btn mode-btn" id="period-30d" data-period="30d">۱ ماهه</button>
                <button class="pill-btn mode-btn" id="period-1y" data-period="1y">یکساله</button>
            </div>
        </div>
    </div>

    <div class="chart-box" style="height: 350px; position: relative;">
        <div id="chart"></div>
    </div>

    <?php if (!isset($hide_stats) || !$hide_stats): ?>
    <div class="d-flex-wrap just-between mt-1 pt-1 border-top">
         <div class="d-flex gap-1">
             <span class="text-gray">بالاترین قیمت (۲۴ساعته):</span>
             <strong class="text-success chart-high-price">---</strong>
         </div>
         <div class="d-flex gap-1">
             <span class="text-gray">پایین‌ترین قیمت (۲۴ساعته):</span>
             <strong class="text-error chart-low-price">---</strong>
         </div>
    </div>
    <?php endif; ?>
</div>
