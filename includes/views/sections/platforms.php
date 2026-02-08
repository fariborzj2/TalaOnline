<div class="w-full lg:w-2/3">
    <div class="glass-card overflow-hidden">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <h3 class="text-xl font-black text-slate-800 dark:text-white flex items-center">
                <span class="w-2 h-8 bg-emerald-500 rounded-full ml-3"></span>
                مقایسه پلتفرم‌های طلا
            </h3>

            <div class="relative w-full sm:w-64">
                <input type="text" id="platform-search" placeholder="جستجوی پلتفرم..." class="w-full h-11 bg-slate-100 dark:bg-slate-700/50 border-none rounded-xl px-11 text-xs font-bold focus:ring-2 focus:ring-primary/50 transition-all">
                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto -mx-6">
            <table class="w-full border-collapse text-right min-w-[800px]">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/30">
                        <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800">نام پلتفرم</th>
                        <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 text-center">قیمت خرید (تومان)</th>
                        <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 text-center">قیمت فروش (تومان)</th>
                        <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 text-center">کارمزد</th>
                        <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 text-center">وضعیت</th>
                        <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody id="platforms-list">
                    <?php foreach ($platforms as $platform): ?>
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors border-b border-slate-50 dark:border-slate-800/50">
                        <td class="px-6 py-5">
                            <div class="flex items-center space-x-reverse space-x-3">
                                <div class="w-10 h-10 rounded-lg bg-white dark:bg-slate-800 p-1.5 shadow-sm">
                                    <img src="<?= htmlspecialchars($platform['logo']) ?>" alt="<?= htmlspecialchars($platform['name']) ?>" class="w-full h-full object-contain">
                                </div>
                                <span class="text-sm font-black text-slate-800 dark:text-slate-200"><?= htmlspecialchars($platform['name']) ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-center text-sm font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">
                            <?= fa_price($platform['buy_price']) ?>
                        </td>
                        <td class="px-6 py-5 text-center text-sm font-bold text-rose-600 dark:text-rose-400 tabular-nums">
                            <?= fa_price($platform['sell_price']) ?>
                        </td>
                        <td class="px-6 py-5 text-center text-xs font-bold text-slate-500 dark:text-slate-400">
                            <?= fa_num($platform['fee']) ?>٪
                        </td>
                        <td class="px-6 py-5 text-center">
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-black <?= $platform['status'] == 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-slate-100 text-slate-500' ?>">
                                <?= $platform['status'] == 'active' ? 'فعال' : 'غیرفعال' ?>
                            </span>
                        </td>
                        <td class="px-6 py-5 text-center">
                            <a href="<?= htmlspecialchars($platform['link']) ?>" target="_blank" class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-500 hover:bg-primary hover:text-white transition-all duration-300">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
