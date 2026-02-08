<div class="glass-card hover:shadow-hover" id="<?= $id ?? '' ?>">
    <div class="flex justify-between items-start mb-4">
        <h3 class="text-sm font-bold text-slate-400"><?= htmlspecialchars($title) ?></h3>
        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white <?= $color ?? 'bg-primary' ?>">
            <span class="text-[10px] font-black"><?= substr($symbol, 0, 1) ?></span>
        </div>
    </div>

    <div class="mb-2">
        <span class="text-2xl font-black text-slate-900 dark:text-white current-price"><?= fa_price($price) ?></span>
    </div>

    <div class="flex items-center gap-1.5 <?= $change >= 0 ? 'text-emerald-500' : 'text-rose-500' ?> change-container">
        <span class="text-xs font-bold trend-icon"><?= $change >= 0 ? '↗' : '↘' ?></span>
        <span class="text-xs font-black change-percent"><?= fa_num(abs($change)) ?>٪</span>
        <span class="text-[10px] font-bold opacity-60">امروز</span>
    </div>
</div>
