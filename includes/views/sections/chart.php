<div class="bg-block pd-md border grow-1 radius-16">
    <div class="d-flex-wrap just-between align-center mb-20 gap-1 mb-1">
        <div class="d-flex gap-1 align-center">
            <div class="w-12 h-12 radius-10 border d-flex just-center align-center">
                <i data-lucide="trending-up" class="icon-size-6"></i>
            </div>
            <div>
                <h2 class="text-title">نمودار قیمت طلا</h2>
                <span class="font-size-0-9">نوسانات قیمت طلا در بازه‌های زمانی مختلف</span>
            </div>
        </div>
        <div class="d-flex-wrap gap-1">
            <?php if (!empty($chart_items)): ?>
            <div class="d-flex gap-05 border-left pl-1 ml-1" id="chart-assets-toggle">
                <?php foreach ($chart_items as $index => $item): ?>
                    <button class="btn btn-secondary btn-sm chart-toggle-btn <?= $index === 0 ? 'active' : '' ?>" data-symbol="<?= htmlspecialchars($item['symbol']) ?>">
                        <?= htmlspecialchars($item['name']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="d-flex gap-05 period-toggle">
                <button class="btn btn-secondary btn-sm mode-btn active" id="period-7d">۷ روز</button>
                <button class="btn btn-secondary btn-sm mode-btn" id="period-30d">۳۰ روز</button>
                <button class="btn btn-secondary btn-sm mode-btn" id="period-1y">۱ سال</button>
            </div>
        </div>
    </div>

    <div class="chart-box" style="height: 350px; position: relative;">
        <div id="chart"></div>
    </div>

    <div class="d-flex just-between mt-1 pt-1 border-top">
         <div class="d-flex gap-1">
             <span class="text-gray">بالاترین قیمت:</span>
             <strong class="text-success chart-high-price">---</strong>
         </div>
         <div class="d-flex gap-1">
             <span class="text-gray">پایین‌ترین قیمت:</span>
             <strong class="text-error chart-low-price">---</strong>
         </div>
    </div>
</div>
