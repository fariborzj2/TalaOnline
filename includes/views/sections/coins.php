<div class="w-full lg:w-1/3">
    <div class="glass-card h-full">
        <div class="flex items-center justify-between mb-8">
            <h3 class="text-xl font-black text-slate-800 dark:text-white flex items-center">
                <span class="w-2 h-8 bg-primary rounded-full ml-3"></span>
                لیست قیمت‌ها
            </h3>
            <div class="text-[10px] font-bold text-slate-400 bg-slate-100 dark:bg-slate-700/50 px-3 py-1 rounded-lg">
                بروزرسانی: هم‌اکنون
            </div>
        </div>

        <div class="space-y-2 overflow-y-auto max-h-[600px] pr-2 custom-scrollbar" id="coins-list">
            <?php foreach ($coins as $index => $coin): ?>
                <?= View::renderComponent('coin_item', [
                    'coin' => $coin,
                    'delay' => 0.2 + ($index * 0.05)
                ]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    @apply bg-slate-200 dark:bg-slate-700 rounded-full;
}
</style>
