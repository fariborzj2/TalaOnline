<div class="block-card d-flex-column just-between basis500 chartbox fade-in-up" style="animation-delay: 0.2s;">
    <div class="d-flex just-between align-start mb-10">
        <div class="d-flex-wrap gap-10">
            <div class="mode-toggle">
                <button id="gold-chart-btn" class="mode-btn active" aria-label="نمایش نمودار طلا">نمودار طلا</button>
                <button id="silver-chart-btn" class="mode-btn" aria-label="نمایش نمودار نقره">نمودار نقره</button>
            </div>
            <div class="mode-toggle period-toggle">
                <button id="period-7d" class="mode-btn active" aria-label="نمایش ۷ روز اخیر">۷ روز</button>
                <button id="period-30d" class="mode-btn" aria-label="نمایش ۳۰ روز اخیر">۳۰ روز</button>
                <button id="period-1y" class="mode-btn" aria-label="نمایش ۱ سال اخیر">۱ سال</button>
            </div>
        </div>

        <div class="d-flex gap-20">
            <div class="">
                <div class="font-size-0-9">بالاترین قیمت</div>
                <div class=""><span class="font-size-1-2 color-title chart-high-price"><?= fa_price($gold_data['high'] ?? null) ?></span> <span class="color-bright">تومان</span></div>
            </div>
            <div class="">
                <div class="font-size-0-9">پایین‌ترین قیمت</div>
                <div class=""><span class="font-size-1-2 color-title chart-low-price"><?= fa_price($gold_data['low'] ?? null) ?></span> <span class="color-bright">تومان</span></div>
            </div>
        </div>
    </div>

    <div id="chart" class="chart"></div>
</div>
