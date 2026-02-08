<div class="w-full lg:w-1/2">
    <div class="glass-card h-full flex flex-col">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div class="flex items-center space-x-reverse space-x-3">
                <div class="p-2.5 bg-primary/10 rounded-xl text-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>
                </div>
                <h3 class="text-lg font-extrabold text-slate-800 dark:text-slate-100">نمودار نوسانات طلا</h3>
            </div>

            <div class="flex items-center bg-slate-100 dark:bg-slate-700/50 p-1 rounded-xl border border-slate-200/50 dark:border-slate-600/30">
                <button class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all duration-300 bg-white dark:bg-slate-600 text-primary shadow-sm active-period" data-period="7d">۷ روز</button>
                <button class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all duration-300 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" data-period="30d">۳۰ روز</button>
                <button class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all duration-300 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" data-period="1y">۱ سال</button>
            </div>
        </div>

        <div class="flex-grow relative min-h-[250px]">
            <div id="main-chart" class="w-full h-full"></div>
            <div id="chart-loader" class="absolute inset-0 flex items-center justify-center bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm z-10 hidden">
                <div class="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
            </div>
        </div>

        <div class="mt-6 flex justify-between items-center px-4 py-3 bg-slate-50 dark:bg-slate-900/30 rounded-2xl border border-slate-100 dark:border-slate-800/50">
            <div class="text-center">
                <p class="text-[10px] text-slate-400 font-bold mb-1">بالاترین قیمت</p>
                <p class="text-sm font-black text-slate-700 dark:text-slate-200" id="chart-high">---</p>
            </div>
            <div class="w-px h-8 bg-slate-200 dark:bg-slate-700"></div>
            <div class="text-center">
                <p class="text-[10px] text-slate-400 font-bold mb-1">پایین‌ترین قیمت</p>
                <p class="text-sm font-black text-slate-700 dark:text-slate-200" id="chart-low">---</p>
            </div>
        </div>
    </div>
</div>
