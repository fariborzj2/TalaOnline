<div class="glass-card group" id="<?= $id ?>" style="<?= isset($delay) ? "animation-delay: {$delay}s;" : "" ?>">
    <div class="flex justify-between items-start mb-6">
        <div class="flex items-center space-x-reverse space-x-4">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center p-3 transition-transform duration-500 group-hover:scale-110 <?= $icon_class == 'gold-icon' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-600' : 'bg-slate-100 dark:bg-slate-700/50 text-slate-500' ?>">
                <img src="assets/images/<?= $icon ?>.svg" alt="<?= htmlspecialchars($title) ?>" class="w-full h-full object-contain">
            </div>
            <div>
                <h3 class="text-lg font-extrabold text-slate-800 dark:text-slate-100"><?= htmlspecialchars($title) ?></h3>
                <p class="text-xs text-slate-500 dark:text-slate-400"><?= htmlspecialchars($subtitle) ?></p>
            </div>
        </div>
        <div class="flex items-center space-x-reverse space-x-1.5 bg-emerald-100/50 dark:bg-emerald-900/20 px-3 py-1 rounded-full border border-emerald-200/50 dark:border-emerald-800/30">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400">زنده</span>
        </div>
    </div>

    <div class="mb-8">
        <div class="flex items-baseline space-x-reverse space-x-2">
            <span class="text-3xl font-black text-slate-900 dark:text-white current-price"><?= fa_price($data['price'] ?? null) ?></span>
            <span class="text-sm font-bold text-slate-400 dark:text-slate-500">تومان</span>
        </div>

        <div class="mt-3 flex items-center space-x-reverse space-x-3">
            <?php
            $change = (float)($data['change'] ?? 0);
            $percent = (float)($data['change_percent'] ?? 0);
            $isPositive = $change >= 0;
            ?>
            <div class="flex items-center space-x-reverse space-x-1 px-2.5 py-1 rounded-lg text-xs font-bold <?= $isPositive ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400' ?>">
                <span><?= $isPositive ? '▲' : '▼' ?></span>
                <span class="change-percent"><?= fa_num(abs($percent)) ?>٪</span>
            </div>
            <span class="text-xs font-medium text-slate-400 dark:text-slate-500 price-change">
                (<?= ($isPositive ? '+' : '') . fa_price($change) ?>)
            </span>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 pt-6 border-t border-slate-100 dark:border-slate-700/50">
        <div>
            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">بیشترین امروز</p>
            <p class="text-sm font-bold text-slate-700 dark:text-slate-200 high-price"><?= fa_price($data['high'] ?? null) ?> <span class="text-[10px] text-slate-400 mr-1">تومان</span></p>
        </div>
        <div>
            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">کمترین امروز</p>
            <p class="text-sm font-bold text-slate-700 dark:text-slate-200 low-price"><?= fa_price($data['low'] ?? null) ?> <span class="text-[10px] text-slate-400 mr-1">تومان</span></p>
        </div>
    </div>
</div>
