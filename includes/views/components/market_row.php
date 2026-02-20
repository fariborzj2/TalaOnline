<?php
$is_platform = $is_platform ?? false;
$name = $item['name'];
$symbol = $item['symbol'] ?? ($is_platform ? 'PLAT' : '');
$price = $item['price'] ?? 0;
$change = $item['change_percent'] ?? 0;
$change_amount = $item['change_amount'] ?? 0;
$is_positive = $change >= 0;

// Random data for empty fields to match image look
$market_cap = fa_num(rand(100, 999)) . 'B';
$volume = fa_num(rand(100, 999)) . ',000.00';
$supply = fa_num(rand(1000, 9999)) . ',000 ' . $symbol;
?>
<tr class="transition-all hover-bg-secondary group">
    <td class="pd-md">
        <div class="d-flex align-center gap-1">
            <div class="w-10 h-10 radius-50 d-flex align-center just-center bg-primary font-black font-size-1 text-white">
                <?= mb_substr($name, 0, 1, 'UTF-8') ?>
            </div>
            <div class="d-column align-start">
                <p class="font-size-2 font-black text-title"><?= htmlspecialchars($name) ?></p>
                <p class="font-size-1 font-bold text-gray uppercase"><?= htmlspecialchars($symbol) ?></p>
            </div>
        </div>
    </td>
    <td class="pd-md">
        <span class="font-size-2 font-black text-subtitle"><?= fa_price($price) ?></span>
    </td>
    <td class="pd-md">
        <div class="d-column gap-05">
            <div class="badge <?= $is_positive ? 'badge-success' : 'badge-error' ?> w-fit">
                <?= ($is_positive ? '+' : '-') . fa_num(abs($change)) ?>٪
            </div>
            <span class="font-size-1 text-gray font-bold ltr">
                <?= ($is_positive ? '+ ' : '- ') . fa_price(abs($change_amount)) ?>
            </span>
        </div>
    </td>
    <td class="pd-md">
        <span class="font-size-2 font-bold text-gray"><?= $market_cap ?></span>
    </td>
    <td class="pd-md">
        <div class="d-column align-start">
            <p class="font-size-2 font-bold text-subtitle"><?= $volume ?></p>
            <p class="font-size-1 font-black <?= rand(0,1) ? 'text-success' : 'text-error' ?>"><?= (rand(0,1) ? '+' : '-') . fa_num(rand(1,5)) ?>.<?= rand(100,999) ?>%</p>
        </div>
    </td>
    <td class="pd-md">
        <span class="font-size-2 font-bold text-gray"><?= $supply ?></span>
    </td>
    <td class="pd-md">
        <!-- Sparkline Placeholder -->
        <div class="w-24 h-10">
            <svg viewBox="0 0 100 40" class="w-full h-full">
                <path d="M 0 <?= rand(20,40) ?> Q 25 <?= rand(0,20) ?> 50 <?= rand(10,30) ?> T 100 <?= rand(0,40) ?>"
                      fill="none"
                      stroke="<?= $is_positive ? '#117856' : '#c81e1e' ?>"
                      stroke-width="2" />
            </svg>
        </div>
    </td>
</tr>
