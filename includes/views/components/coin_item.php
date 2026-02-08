<div class="group flex items-center justify-between p-4 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-all duration-300 border border-transparent hover:border-slate-100 dark:hover:border-slate-700/50 fade-in-up" style="animation-delay: <?= $delay ?>s">
    <div class="flex items-center space-x-reverse space-x-4">
        <div class="w-12 h-12 rounded-xl bg-white dark:bg-slate-800 shadow-sm flex items-center justify-center p-2 group-hover:scale-110 transition-transform duration-300">
            <img src="assets/images/coin/<?= $coin['symbol'] ?>.svg" alt="<?= htmlspecialchars($coin['name']) ?>" class="w-full h-full object-contain" onerror="this.src='assets/images/gold.svg'">
        </div>
        <div>
            <h4 class="text-sm font-extrabold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($coin['name']) ?></h4>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter"><?= htmlspecialchars($coin['symbol']) ?></p>
        </div>
    </div>

    <div class="text-left">
        <p class="text-sm font-black text-slate-900 dark:text-white"><?= fa_price($coin['price']) ?></p>
        <div class="flex items-center justify-end space-x-reverse space-x-1.5">
            <?php $isPos = $coin['change_percent'] >= 0; ?>
            <span class="text-[10px] font-bold <?= $isPos ? 'text-emerald-500' : 'text-rose-500' ?>">
                <?= $isPos ? '+' : '' ?><?= fa_num($coin['change_percent']) ?>٪
            </span>
        </div>
    </div>
</div>
