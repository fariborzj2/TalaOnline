<div class="glass-card !p-0 overflow-hidden">
    <div class="p-8 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
        <h2 class="text-xl font-black text-slate-900 dark:text-white">عملکرد بازار</h2>
        <div class="flex gap-4">
             <button class="p-2.5 rounded-xl border border-slate-100 dark:border-slate-700 text-slate-400 hover:text-primary transition-colors" aria-label="تنظیمات">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="2" y1="14" x2="6" y2="14"></line><line x1="10" y1="8" x2="14" y2="8"></line><line x1="18" y1="16" x2="22" y2="16"></line></svg>
             </button>
             <button class="p-2.5 rounded-xl border border-slate-100 dark:border-slate-700 text-slate-400 hover:text-primary transition-colors" aria-label="بیشتر">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
             </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-right border-collapse">
            <thead>
                <tr class="text-xs font-black text-slate-400 uppercase tracking-wider">
                    <th class="px-8 py-6">دارایی</th>
                    <th class="px-8 py-6">قیمت (تومان)</th>
                    <th class="px-8 py-6">تغییر ۲۴ ساعته</th>
                    <th class="px-8 py-6">ارزش بازار</th>
                    <th class="px-8 py-6">حجم (۲۴ ساعت)</th>
                    <th class="px-8 py-6">عرضه در گردش</th>
                    <th class="px-8 py-6">نمودار</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                <?php foreach ($coins as $coin): ?>
                    <?= View::renderComponent('market_row', ['item' => $coin]) ?>
                <?php endforeach; ?>
                <?php foreach ($platforms as $platform): ?>
                    <?= View::renderComponent('market_row', ['item' => $platform, 'is_platform' => true]) ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
