<div class="block-card price-card fade-in-up" id="<?= $id ?>" style="<?= isset($delay) ? "animation-delay: {$delay}s;" : "" ?>">
    <div class="card-header d-flex just-between align-center mb-15">
        <div class="d-flex align-center gap-10">
            <div class="asset-icon <?= $icon_class ?>">
                <img src="assets/images/<?= $icon ?>.svg" alt="<?= htmlspecialchars($title) ?>">
            </div>
            <div>
                <div class="asset-name color-title font-bold"><?= htmlspecialchars($title) ?></div>
                <div class="font-size-0-8 color-bright"><?= htmlspecialchars($subtitle) ?></div>
            </div>
        </div>
        <div class="live-tag">
            <span class="pulse-dot"></span>
            زنده
        </div>
    </div>

    <div class="price-main mb-20">
        <div class="d-flex align-baseline">
            <span class="color-title font-size-2-5 font-bold current-price"><?= fa_price($data['price'] ?? null) ?></span>
            <span class="color-bright font-size-1 font-bold mr-10">تومان</span>
        </div>
        <div class="trend-badge-wrapper mt-5 d-flex align-center gap-10">
            <?php
            $change = (float)($data['change'] ?? 0);
            $percent = (float)($data['change_percent'] ?? 0);
            $badge_class = $change >= 0 ? 'color-green' : 'color-red';
            $sign = $change > 0 ? '+' : '';
            ?>
            <span class="trend-badge change-percent <?= $badge_class ?>"><?= get_trend_arrow($change) ?><?= fa_num($percent) ?>٪</span>
            <span class="price-change-val price-change">(<?= $sign . fa_price($change) ?>)</span>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stats-item">
            <div class="stats-label">بیشترین امروز</div>
            <div class="stats-value high-price"><?= fa_price($data['high'] ?? null) ?> <span class="font-size-0-7 color-bright">تومان</span></div>
        </div>
        <div class="stats-item">
            <div class="stats-label">کمترین امروز</div>
            <div class="stats-value low-price"><?= fa_price($data['low'] ?? null) ?> <span class="font-size-0-7 color-bright">تومان</span></div>
        </div>
    </div>
</div>
