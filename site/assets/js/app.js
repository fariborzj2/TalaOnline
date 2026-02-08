document.addEventListener('DOMContentLoaded', async function() {
    let state = {
        platforms: [],
        currentSort: { column: null, direction: 'asc' }
    };

    const persianNumberFormatter = new Intl.NumberFormat('fa-IR');
    const toPersianDigits = (num) => {
        if (num === null || num === undefined) return '';
        return persianNumberFormatter.format(num);
    };

    const formatPrice = (price) => toPersianDigits(price);

    const fetchData = async () => {
        try {
            const response = await fetch('api/dashboard.php');
            if (!response.ok) throw new Error('Failed to fetch dashboard data');
            return await response.json();
        } catch (error) {
            console.error('Error loading dashboard data:', error);
            return null;
        }
    };

    const populateSummary = (summary) => {
        ['gold', 'silver'].forEach(asset => {
            const container = document.getElementById(`${asset}-summary`);
            if (!container) return;

            const data = summary[asset];
            const isPositive = data.change_percent >= 0;

            const currentPriceEl = container.querySelector('.current-price');
            if (currentPriceEl) currentPriceEl.textContent = formatPrice(data.price);

            const changeContainer = container.querySelector('.change-container');
            const percentEl = container.querySelector('.change-percent');
            const iconEl = container.querySelector('.trend-icon');

            if (percentEl) percentEl.textContent = toPersianDigits(Math.abs(data.change_percent)) + '٪';
            if (iconEl) iconEl.textContent = isPositive ? '↗' : '↘';

            if (changeContainer) {
                if (isPositive) {
                    changeContainer.classList.remove('text-rose-500');
                    changeContainer.classList.add('text-emerald-500');
                } else {
                    changeContainer.classList.remove('text-emerald-500');
                    changeContainer.classList.add('text-rose-500');
                }
            }
        });
    };

    const populatePlatforms = (platforms) => {
        const tbody = document.getElementById('platforms-list');
        if (!tbody) return;

        if (platforms.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-12 text-center text-slate-400 font-bold">موردی یافت نشد.</td></tr>`;
            return;
        }

        tbody.innerHTML = platforms.map(p => `
            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors border-b border-slate-50 dark:border-slate-800/50 text-right">
                <td class="px-6 py-5">
                    <div class="flex items-center space-x-reverse space-x-3">
                        <div class="w-10 h-10 rounded-lg bg-white dark:bg-slate-800 p-1.5 shadow-sm">
                            <img src="${p.logo}" alt="${p.name}" class="w-full h-full object-contain">
                        </div>
                        <span class="text-sm font-black text-slate-800 dark:text-slate-200">${p.name}</span>
                    </div>
                </td>
                <td class="px-6 py-5 text-center text-sm font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">
                    ${formatPrice(p.buy_price)}
                </td>
                <td class="px-6 py-5 text-center text-sm font-bold text-rose-600 dark:text-rose-400 tabular-nums">
                    ${formatPrice(p.sell_price)}
                </td>
                <td class="px-6 py-5 text-center text-xs font-bold text-slate-500 dark:text-slate-400">
                    ${toPersianDigits(p.fee)}٪
                </td>
                <td class="px-6 py-5 text-center">
                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-black ${p.status === 'active' || p.status === 'فعال' || p.status === 'مناسب خرید' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-slate-100 text-slate-500'}">
                        ${p.status === 'active' ? 'فعال' : (p.status || 'نامشخص')}
                    </span>
                </td>
                <td class="px-6 py-5 text-center">
                    <a href="${p.link}" target="_blank" class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-500 hover:bg-primary hover:text-white transition-all duration-300">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                    </a>
                </td>
            </tr>
        `).join('');
    };

    const populateCoins = (coins) => {
        const container = document.getElementById('coins-list');
        if (!container) return;

        container.innerHTML = coins.map((c, index) => {
            const isPos = c.change_percent >= 0;
            return `
                <div class="group flex items-center justify-between p-4 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-all duration-300 border border-transparent hover:border-slate-100 dark:hover:border-slate-700/50 fade-in-up" style="animation-delay: ${0.2 + (index * 0.05)}s">
                    <div class="flex items-center space-x-reverse space-x-4">
                        <div class="w-12 h-12 rounded-xl bg-white dark:bg-slate-800 shadow-sm flex items-center justify-center p-2 group-hover:scale-110 transition-transform duration-300">
                            <img src="assets/images/coin/${c.symbol}.svg" alt="${c.name}" class="w-full h-full object-contain" onerror="this.src='assets/images/gold.svg'">
                        </div>
                        <div>
                            <h4 class="text-sm font-extrabold text-slate-800 dark:text-slate-200">${c.name}</h4>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">${c.symbol}</p>
                        </div>
                    </div>
                    <div class="text-left text-left-important">
                        <p class="text-sm font-black text-slate-900 dark:text-white">${formatPrice(c.price)}</p>
                        <div class="flex items-center justify-end space-x-reverse space-x-1.5">
                            <span class="text-[10px] font-bold ${isPos ? 'text-emerald-500' : 'text-rose-500'}">
                                ${isPos ? '+' : ''}${toPersianDigits(c.change_percent)}٪
                            </span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    };

    const initSearch = () => {
        const searchInput = document.getElementById('platform-search');
        if (!searchInput) return;

        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            const filtered = state.platforms.filter(p =>
                p.name.toLowerCase().includes(query) ||
                (p.en_name && p.en_name.toLowerCase().includes(query))
            );
            populatePlatforms(filtered);
        });
    };

    const initTheme = () => {
        const themeToggle = document.getElementById('theme-toggle');
        const setTheme = (theme) => {
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            localStorage.setItem('theme', theme);
            window.dispatchEvent(new CustomEvent('themechanged', { detail: { theme } }));
        };

        const currentTheme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        setTheme(currentTheme);

        themeToggle.addEventListener('click', () => {
            const isDark = document.documentElement.classList.contains('dark');
            setTheme(isDark ? 'light' : 'dark');
        });
    };

    const initApp = async () => {
        initTheme();
        const banner = document.getElementById('error-banner');
        if (banner) banner.classList.add('hidden');

        if (window.__INITIAL_STATE__) {
            const data = window.__INITIAL_STATE__;
            state.platforms = data.platforms;
            initSearch();
            console.log('Initialized from SSR state');
        } else {
            const data = await fetchData();
            if (data) {
                state.platforms = data.platforms;
                populateSummary(data.summary);
                populatePlatforms(state.platforms);
                populateCoins(data.coins);
                initSearch();
            } else {
                if (banner) banner.classList.remove('hidden');
            }
        }
    };

    const reloadBtn = document.getElementById('reload-btn');
    if (reloadBtn) {
        reloadBtn.addEventListener('click', () => {
            initApp();
        });
    }

    await initApp();
});
