<div class="bg-block pd-md border basis-250 grow-1 radius-16">
    <div class="d-flex-wrap just-between align-center mb-20 gap-1 mb-1">
        <div class="d-flex gap-1 align-center">
            <div class="w-12 h-12 radius-10 border d-flex just-center align-center">
                <i data-lucide="scale" class="icon-size-6"></i>
            </div>
            <div>
                <h2 class="text-title">مقایسه پلتفرم های طلا آنلاین</h2>
                <span class="font-size-0-9">با بررسی قیمت ها بهترین پتلفرم را برای معامله انتخاب کنید</span>
            </div>
        </div>
        <div class="basis-300">
            <div class="input-item">
                <i data-lucide="search" class="icon-size-4"></i>
                <input id="platform-search" placeholder="جستجوی پلتفرم‌ها" type="text">
            </div>
        </div>
    </div>

    <div class="table-box">
        <table class="full-width">
            <thead>
                <tr>
                    <th>لوگو</th>
                    <th class="sortable" data-sort="name">
                        <div class="d-flex align-center">نام پلتفرم <span class="sort-icon"></span></div>
                    </th>
                    <th class="sortable" data-sort="success_price">
                        <div class="d-flex align-center">قیمت خرید (تومان) <span class="sort-icon"></span></div>
                    </th>
                    <th class="sortable" data-sort="error_price">
                        <div class="d-flex align-center">قیمت فروش (تومان) <span class="sort-icon"></span></div>
                    </th>
                    <th class="sortable" data-sort="fee">
                        <div class="d-flex align-center">کارمزد <span class="sort-icon"></span></div>
                    </th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody id="platforms-list">
                <?php
                $min_buy = null;
                $max_sell = null;
                foreach ($platforms as $p) {
                    $buy = (float)($p['buy_price'] ?? 0);
                    $sell = (float)($p['sell_price'] ?? 0);
                    $fee = (float)($p['fee'] ?? 0);
                    $eff_buy = $buy * (1 + $fee / 100);
                    $eff_sell = $sell * (1 - $fee / 100);
                    if ($min_buy === null || $eff_buy < $min_buy) $min_buy = $eff_buy;
                    if ($max_sell === null || $eff_sell > $max_sell) $max_sell = $eff_sell;
                }
                ?>
                <?php foreach ($platforms as $platform): ?>
                    <?php
                    $buy = (float)($platform['buy_price'] ?? 0);
                    $sell = (float)($platform['sell_price'] ?? 0);
                    $fee = (float)($platform['fee'] ?? 0);
                    $eff_buy = $buy * (1 + $fee / 100);
                    $eff_sell = $sell * (1 - $fee / 100);

                    $is_best_buy = ($min_buy !== null && $eff_buy <= $min_buy);
                    $is_best_sell = ($max_sell !== null && $eff_sell >= $max_sell);

                    $status_text = 'عادی';
                    $status_class = 'warning';

                    if ($is_best_buy) {
                        $status_text = 'مناسب خرید';
                        $status_class = 'success';
                    } elseif ($is_best_sell) {
                        $status_text = 'مناسب فروش';
                        $status_class = 'info';
                    }
                    ?>
                    <tr>
                        <td>
                            <div class="brand-logo"> <img src="<?= htmlspecialchars(get_asset_url($platform['logo'])) ?>" alt="<?= htmlspecialchars($platform['name']) ?>" loading="lazy" decoding="async" width="32" height="32"> </div>
                        </td>
                        <td>
                            <div class="line20">
                                <div class="text-title"><?= htmlspecialchars($platform['name']) ?></div>
                                <div class="font-size-0-8"><?= htmlspecialchars($platform['en_name'] ?? '') ?></div>
                            </div>
                        </td>
                        <td class="font-size-2 font-bold text-title"><?= fa_price($platform['buy_price']) ?></td>
                        <td class="font-size-2 font-bold text-title"><?= fa_price($platform['sell_price']) ?></td>
                        <td class="font-size-2 " dir="ltr"><?= fa_num($platform['fee']) ?>%</td>
                        <td>
                            <span class="status-badge <?= $status_class ?>">
                                <?= $status_text ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= htmlspecialchars($platform['link']) ?>" class="btn btn-secondary btn-sm" target="_blank" rel="noopener noreferrer">
                                <i data-lucide="external-link" class="h-4 w-4"></i> خرید طلا
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
