document.addEventListener('DOMContentLoaded', async function() {
    console.log('App script initialized');

    let state = {
        platforms: [],
        currentSort: { column: null, direction: 'asc' }
    };

    const persianNumberFormatter = new Intl.NumberFormat('fa-IR');

    const toPersianDigits = (num) => {
        if (num === null || num === undefined) return '';
        return persianNumberFormatter.format(num);
    };

    const formatPrice = (price) => {
        return toPersianDigits(price);
    };

    const fetchData = async () => {
        try {
            const response = await fetch('data/dashboard.json');
            if (!response.ok) throw new Error('Failed to fetch dashboard data');
            return await response.json();
        } catch (error) {
            console.error('Error loading dashboard data:', error);
            return null;
        }
    };

    const getTrendArrow = (change) => {
        if (change > 0) return '<span class="trend-arrow trend-up"></span>';
        if (change < 0) return '<span class="trend-arrow trend-down"></span>';
        return '';
    };

    const populateSummary = (summary) => {
        const assets = ['gold', 'silver'];
        assets.forEach(asset => {
            const container = document.getElementById(`${asset}-summary`);
            if (!container) return;

            const data = summary[asset];

            const currentPriceEl = container.querySelector('.current-price');
            currentPriceEl.textContent = formatPrice(data.current);
            currentPriceEl.classList.remove('skeleton');

            const priceChangeEl = container.querySelector('.price-change');
            const sign = data.change > 0 ? '+' : '';
            priceChangeEl.textContent = `(${sign}${formatPrice(data.change)})`;
            priceChangeEl.classList.remove('skeleton');

            const percentEl = container.querySelector('.change-percent');
            percentEl.innerHTML = getTrendArrow(data.change) + toPersianDigits(data.change_percent) + '٪';
            percentEl.className = `trend-badge change-percent ${data.change >= 0 ? 'color-green' : 'color-red'}`;
            percentEl.classList.remove('skeleton');

            const highPriceEl = container.querySelector('.high-price');
            highPriceEl.innerHTML = `${formatPrice(data.high)} <span class="font-size-0-7 color-bright">تومان</span>`;
            highPriceEl.classList.remove('skeleton');

            const lowPriceEl = container.querySelector('.low-price');
            lowPriceEl.innerHTML = `${formatPrice(data.low)} <span class="font-size-0-7 color-bright">تومان</span>`;
            lowPriceEl.classList.remove('skeleton');
        });

        // Also update the chart high/low placeholders if needed
        const chartHighEl = document.querySelector('.chart-high-price');
        chartHighEl.textContent = formatPrice(summary.gold.high);
        chartHighEl.classList.remove('skeleton');

        const chartLowEl = document.querySelector('.chart-low-price');
        chartLowEl.textContent = formatPrice(summary.gold.low);
        chartLowEl.classList.remove('skeleton');
    };

    const populatePlatforms = (platforms) => {
        const tbody = document.getElementById('platforms-table-body');
        if (!tbody) return;

        if (platforms.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center" style="padding: 40px; color: var(--color-bright);">
                <div class="mb-10 font-size-1-5">🔍</div>
                نتیجه‌ای برای جستجوی شما یافت نشد.
            </td></tr>`;
            return;
        }

        tbody.innerHTML = platforms.map(p => `
            <tr>
                <td>
                    <div class="brand-logo">
                        <img src="${p.logo}" alt="${p.name}">
                    </div>
                </td>
                <td>
                    <div class="line20">
                        <div class="color-title">${p.name}</div>
                        <div class="font-size-0-8">${p.en_name}</div>
                    </div>
                </td>
                <td class="font-size-1-2 color-title">${formatPrice(p.buy_price)}</td>
                <td class="font-size-1-2 color-title">${formatPrice(p.sell_price)}</td>
                <td class="font-size-1-2" dir="ltr">${toPersianDigits(p.fee)}٪</td>
                <td>
                    <span class="status-badge ${p.status === 'مناسب خرید' ? 'buy' : 'sell'}">
                        ${p.status}
                    </span>
                </td>
                <td>
                    <a href="${p.link}" class="btn" target="_blank" rel="noopener noreferrer" aria-label="خرید طلا از ${p.name} (در پنجره جدید)">خرید طلا</a>
                </td>
            </tr>
        `).join('');
    };

    const initSorting = () => {
        document.querySelectorAll('th.sortable').forEach(th => {
            th.addEventListener('click', () => {
                const column = th.dataset.sort;
                const direction = state.currentSort.column === column && state.currentSort.direction === 'asc' ? 'desc' : 'asc';

                state.currentSort = { column, direction };

                // Update UI classes
                document.querySelectorAll('th.sortable').forEach(el => {
                    el.classList.remove('active-sort', 'sort-asc', 'sort-desc');
                    el.removeAttribute('aria-sort');
                });
                th.classList.add('active-sort', `sort-${direction}`);
                th.setAttribute('aria-sort', direction === 'asc' ? 'ascending' : 'descending');

                // Sort data
                state.platforms.sort((a, b) => {
                    let valA = a[column];
                    let valB = b[column];

                    if (column === 'name') {
                        return direction === 'asc' ? valA.localeCompare(valB, 'fa') : valB.localeCompare(valA, 'fa');
                    }

                    return direction === 'asc' ? valA - valB : valB - valA;
                });

                populatePlatforms(state.platforms);
            });
        });
    };

    const populateCoins = (coins) => {
        const container = document.getElementById('coins-list');
        if (!container) return;

        container.innerHTML = coins.map(c => `
            <div class="coin-item">
                <div class="d-flex align-center gap-10">
                    <div class="brand-logo">
                        <img src="${c.logo}" alt="${c.name}">
                    </div>
                    <div class="line24">
                        <div class="color-title">${c.name}</div>
                        <div class="">${c.en_name}</div>
                    </div>
                </div>

                <div class="line24 text-left">
                    <div class=""><span class="color-title font-size-1-2 font-bold">${formatPrice(c.price)}</span> <span class="color-bright">تومان</span></div>
                    <div class="${c.change_percent >= 0 ? 'color-green' : 'color-red'}">${getTrendArrow(c.change_percent)}${toPersianDigits(c.change_percent)}%</div>
                </div>
            </div>
        `).join('');
    };

    const initSearch = () => {
        const searchInput = document.getElementById('platform-search');
        if (!searchInput) return;

        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            const filtered = state.platforms.filter(p =>
                p.name.toLowerCase().includes(query) ||
                p.en_name.toLowerCase().includes(query)
            );
            populatePlatforms(filtered);

            // Announce result count to screen readers
            const announcement = document.getElementById('search-announcement');
            if (announcement) {
                if (filtered.length > 0) {
                    announcement.textContent = `${toPersianDigits(filtered.length)} مورد یافت شد.`;
                } else {
                    announcement.textContent = 'موردی یافت نشد.';
                }
            }
        });
    };

    const initApp = async () => {
        const banner = document.getElementById('error-banner');
        if (banner) banner.classList.add('d-none');

        // Update current date
        const updateDate = () => {
            const now = new Date();
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            const formattedDate = new Intl.DateTimeFormat('fa-IR', options).format(now);
            const dateEl = document.getElementById('current-date');
            if (dateEl) dateEl.textContent = formattedDate;
        };
        updateDate();

        // Add skeletons back if they were removed
        document.querySelectorAll('.current-price, .price-change, .change-percent, .high-price, .low-price, .chart-high-price, .chart-low-price').forEach(el => {
            if (el.textContent === '---' || el.textContent === '') {
                el.classList.add('skeleton');
            }
        });

        const data = await fetchData();
        if (data) {
            state.platforms = data.platforms;
            populateSummary(data.summary);
            populatePlatforms(state.platforms);
            populateCoins(data.coins);
            initSorting();
            initSearch();
        } else {
            if (banner) banner.classList.remove('d-none');
            // Hide skeletons if error
            document.querySelectorAll('.skeleton').forEach(el => el.classList.remove('skeleton'));
        }
    };

    // Reload button handler
    const reloadBtn = document.getElementById('reload-btn');
    if (reloadBtn) {
        reloadBtn.addEventListener('click', () => {
            initApp();
        });
    }

    await initApp();
});
