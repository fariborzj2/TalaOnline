<div class="coin-item">
    <div class="d-flex align-center gap-10">
        <div class="brand-logo">
            <img src="<?= htmlspecialchars($item['logo']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
        </div>
        <div class="line24">
            <div class="color-title font-size-1"><?= htmlspecialchars($item['name']) ?></div>
            <div class="font-size-0-8"><?= htmlspecialchars($item['en_name']) ?></div>
        </div>
    </div>

    <div class="line24 text-left">
        <div class=""><span class="color-title font-size-1-2 font-bold"><?= fa_price($item['price']) ?></span> <span class="color-bright font-size-0-8">تومان</span></div>
        <div class="<?= (float)$item['change_percent'] >= 0 ? 'color-green' : 'color-red' ?> font-size-0-8 mt-4">
            <?= get_trend_arrow($item['change_percent']) ?><?= fa_num($item['change_percent']) ?>٪
        </div>
    </div>
</div>
