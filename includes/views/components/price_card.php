<div class="card asset-item" id="<?= $id ?? '' ?>" data-asset='<?= htmlspecialchars(json_encode([
    "symbol" => $symbol,
    "slug" => $slug ?? $symbol,
    "category" => $category ?? "",
    "name" => $title,
    "price" => $price,
    "change" => $change,
    "change_amount" => $change_amount ?? 0,
    "image" => get_asset_url($image ?? ""),
    "high" => $high ?? $price,
    "low" => $low ?? $price
]), ENT_QUOTES, "UTF-8") ?>'>
    <div class="d-flex just-between align-center gap-1">
        <div class="d-flex align-center gap-1 mb-1">
            <div class="w-10 h-10 border radius-12 p-05 bg-secondary d-flex align-center just-center">
                <img src="<?= get_asset_url($image ?? '') ?>" alt="<?= htmlspecialchars($title) ?>" decoding="async" width="30" height="30" class="object-contain">
            </div>
            <div class="line-height-1-5">
                <h2 class="font-size-2"><?= htmlspecialchars($title) ?></h2>
                <span class="text-gray"><?= htmlspecialchars($symbol) ?></span>
            </div>
        </div>
    </div>
    <div class="line-height-1-5 text-right" dir="ltr">
       <strong class="font-size-6 current-price"><?= fa_price($price) ?></strong>
        <div class="d-flex just-end align-center gap-1 change-container">
            <?php if (isset($change_amount)): ?>
                <span class="text-gray change-amount"><?= ($change >= 0 ? '+ ' : '- ') . fa_price(abs($change_amount)) ?></span>
            <?php endif; ?>
            <span class="d-flex align-center gap-05 <?= $change >= 0 ? 'text-success' : 'text-error' ?>">
                <span class="font-size-3 change-percent"><?= fa_num(abs($change)) ?>%</span>
                <i data-lucide="<?= $change >= 0 ? 'arrow-up' : 'arrow-down' ?>" class="icon-size-1"></i>
            </span>
        </div>
    </div>
</div>
