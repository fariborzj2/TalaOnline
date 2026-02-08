<?php
$is_platform = $is_platform ?? false;
$name = $item['name'];
$symbol = $item['symbol'] ?? ($is_platform ? 'PLAT' : '');
$price = $item['price'] ?? 0;
$change = $item['change_percent'] ?? 0;
$is_positive = $change >= 0;

// Random data for empty fields to match image look
$market_cap = fa_num(rand(100, 999)) . 'B';
$volume = fa_num(rand(100, 999)) . ',000.00';
$supply = fa_num(rand(1000, 9999)) . ',000 ' . $symbol;
?>
<tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors group">
    <td class="px-8 py-5">
        <?php
        $colors = ['bg-orange-500', 'bg-blue-600', 'bg-yellow-500', 'bg-emerald-500', 'bg-indigo-500', 'bg-blue-400'];
        $bg_color = $colors[array_rand($colors)];
        ?>
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white <?= $bg_color ?> font-black text-xs shadow-lg shadow-black/5">
                <?= substr($name, 0, 1) ?>
            </div>
            <div>
                <p class="text-sm font-black text-slate-900 dark:text-white"><?= htmlspecialchars($name) ?></p>
                <p class="text-[10px] font-bold text-slate-400"><?= htmlspecialchars($symbol) ?></p>
            </div>
        </div>
    </td>
    <td class="px-8 py-5">
        <span class="text-sm font-black text-slate-700 dark:text-slate-200"><?= fa_price($price) ?></span>
    </td>
    <td class="px-8 py-5">
        <div class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black <?= $is_positive ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' ?>">
            <?= ($is_positive ? '+' : '') . fa_num($change) ?>٪
        </div>
    </td>
    <td class="px-8 py-5">
        <span class="text-sm font-bold text-slate-500"><?= $market_cap ?></span>
    </td>
    <td class="px-8 py-5">
        <div>
            <p class="text-sm font-bold text-slate-700 dark:text-slate-200"><?= $volume ?></p>
            <p class="text-[10px] font-black <?= rand(0,1) ? 'text-emerald-500' : 'text-rose-500' ?>"><?= (rand(0,1) ? '+' : '-') . fa_num(rand(1,5)) ?>.<?= rand(100,999) ?>%</p>
        </div>
    </td>
    <td class="px-8 py-5">
        <span class="text-sm font-bold text-slate-500"><?= $supply ?></span>
    </td>
    <td class="px-8 py-5">
        <!-- Sparkline Placeholder -->
        <div class="w-24 h-10">
            <svg viewBox="0 0 100 40" class="w-full h-full">
                <path d="M 0 <?= rand(20,40) ?> Q 25 <?= rand(0,20) ?> 50 <?= rand(10,30) ?> T 100 <?= rand(0,40) ?>"
                      fill="none"
                      stroke="<?= $is_positive ? '#10b981' : '#f43f5e' ?>"
                      stroke-width="2" />
            </svg>
        </div>
    </td>
</tr>
