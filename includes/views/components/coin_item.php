<a href="/<?= ($coin['category'] ? $coin['category'] . '/' : '') ?><?= $coin['slug'] ?? $coin['symbol'] ?>" class="item-list asset-item">
    <div class="d-flex align-center gap-1">
        <div class="w-10 h-10 border radius-10 p-05 bg-secondary">
            <img src="<?= $image ?? '/assets/images/gold/gold.webp' ?>" alt="<?= htmlspecialchars($coin['name']) ?>" loading="lazy" decoding="async" width="32" height="32">
        </div>
        <div class="line-height-1-5">
            <h3 class="font-size-2"><?= htmlspecialchars($coin['name']) ?></h3>
            <span class="text-gray"><?= htmlspecialchars($coin['symbol']) ?></span>
        </div>
    </div>
    <div class="line-height-1-5 text-left" dir="ltr">
        <strong class="font-size-2"><?= fa_price($coin['price']) ?></strong>
        <div class="d-flex align-center gap-05">
            <span class="d-flex align-center gap-05 <?= $coin['change_percent'] >= 0 ? 'text-success' : 'text-error' ?>">
                <span class="font-size-2"><?= fa_num(abs($coin['change_percent'])) ?>%</span>
                <i data-lucide="<?= $coin['change_percent'] >= 0 ? 'arrow-up' : 'arrow-down' ?>" class="icon-size-1"></i>
            </span>
            <?php if (isset($coin['change_amount'])): ?>
                <span class="text-gray"><?= ($coin['change_percent'] >= 0 ? '+ ' : '- ') . fa_price(abs($coin['change_amount'])) ?></span>
            <?php endif; ?>
        </div>
    </div>
</a>
