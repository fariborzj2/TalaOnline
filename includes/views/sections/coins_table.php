<div class="bg-block border radius-16 nowrap overflow-hidden">
    <div class="d-flex just-between align-center gap-1 pd-md border-bottom">
        <div class="d-flex align-center gap-1">
            <div class="w-10 h-10 border radius-12 p-05 bg-secondary d-flex align-center just-center">
                <i data-lucide="<?= $icon ?? 'coins' ?>" color="var(--color-primary)" class="w-6 h-6"></i>
            </div>
            <div class="line-height-1-5">
                <h2 class="font-size-2"><?= $title ?? 'بازار طلا و سکه' ?></h2>
                <span class="text-gray"><?= $subtitle ?? 'gold market' ?></span>
            </div>
        </div>
    </div>

    <div class="table-box" style="border:none; border-radius:none">
        <table class="full-width">
            <thead>
                <tr>
                    <th>لوگو</th>
                    <th>نام دارایی</th>
                    <th>قیمت لحظه‌ای (تومان)</th>
                    <th>بیشترین امروز</th>
                    <th>کمترین امروز</th>
                    <th>تغییر</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody id="<?= $id ?? 'coins-list' ?>">
                <?php foreach ($coins as $coin):
                    $image = ($coin['logo'] ?? '') ?: '/assets/images/gold/' . (strpos($coin['name'] ?? '', 'نیم') !== false ? 'nim' : (strpos($coin['name'] ?? '', 'ربع') !== false ? 'rob' : 'gold')) . '.webp';
                    if ($image && !str_starts_with($image, '/') && !str_starts_with($image, 'http')) {
                        $image = '/' . $image;
                    }
                ?>
                    <tr class="asset-item" data-asset="<?= htmlspecialchars(json_encode([
                        "symbol" => $coin["symbol"],
                        "slug" => $coin["slug"] ?? $coin["symbol"],
                        "category" => $coin["category"] ?? "",
                        "name" => $coin["name"],
                        "price" => $coin["price"],
                        "change" => $coin["change_percent"],
                        "change_amount" => $coin["change_amount"] ?? 0,
                        "image" => $image,
                        "high" => $coin["high"] ?? $coin["price"],
                        "low" => $coin["low"] ?? $coin["price"]
                    ]), ENT_QUOTES, 'UTF-8') ?>">
                        <td>
                             <div class="w-10 h-10 border radius-10 p-05 bg-secondary mg-auto">
                                <img src="<?= $image ?>" alt="<?= htmlspecialchars($coin['name']) ?>" class="radius-8" loading="lazy" decoding="async" width="32" height="32">
                            </div>
                        </td>
                        <td class="just-start">
                            <div class="line-height-1-5 text-right pr-1">
                                <div class="text-title font-bold"><?= htmlspecialchars($coin['name']) ?></div>
                                <div class="font-size-0-8 text-gray"><?= htmlspecialchars($coin['symbol']) ?></div>
                            </div>
                        </td>
                        <td class="font-size-2 font-bold text-title" dir="ltr"><?= fa_price($coin['price']) ?></td>
                        <td class="font-size-2 text-success font-bold" dir="ltr"><?= fa_price($coin['high'] ?? $coin['price']) ?></td>
                        <td class="font-size-2 text-error font-bold" dir="ltr"><?= fa_price($coin['low'] ?? $coin['price']) ?></td>
                        <td>
                            <div class="d-flex align-center gap-05 <?= $coin['change_percent'] >= 0 ? 'text-success' : 'text-error' ?>" dir="ltr">
                                <span class="font-bold"><?= fa_num(abs($coin['change_percent'])) ?>%</span>
                                <i data-lucide="<?= $coin['change_percent'] >= 0 ? 'arrow-up' : 'arrow-down' ?>" class="icon-size-2"></i>
                            </div>
                        </td>
                        <td>
                            <button class="btn btn-secondary btn-sm radius-10 mg-auto">
                                <i data-lucide="line-chart" class="icon-size-4"></i>
                                نمودار
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
