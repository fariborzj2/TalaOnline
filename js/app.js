document.addEventListener('DOMContentLoaded', async function() {
    console.log('App script initialized');

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
            priceChangeEl.textContent = formatPrice(data.change);
            priceChangeEl.classList.remove('skeleton');

            const percentEl = container.querySelector('.change-percent');
            percentEl.innerHTML = getTrendArrow(data.change) + toPersianDigits(data.change_percent) + '٪';
            percentEl.className = data.change >= 0 ? 'color-green change-percent' : 'color-red change-percent';
            percentEl.classList.remove('skeleton');

            const highPriceEl = container.querySelector('.high-price');
            highPriceEl.textContent = formatPrice(data.high);
            highPriceEl.classList.remove('skeleton');

            const lowPriceEl = container.querySelector('.low-price');
            lowPriceEl.textContent = formatPrice(data.low);
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

        tbody.innerHTML = platforms.map(p => `
            <tr>
                <td>
                    <div class="d-flex align-center gap-10">
                        <div class="brand-logo">
                            <img src="${p.logo}" alt="${p.name}">
                        </div>
                        <div class="line20">
                            <div class="color-title">${p.name}</div>
                            <div class="font-size-0-8">${p.en_name}</div>
                        </div>
                    </div>
                </td>
                <td class="font-size-1-2 color-title">${formatPrice(p.buy_price)}</td>
                <td class="font-size-1-2 color-title">${formatPrice(p.sell_price)}</td>
                <td class="font-size-1-2" dir="ltr">${toPersianDigits(p.fee)}</td>
                <td class="color-${p.status_color}">${p.status}</td>
                <td>
                    <a href="${p.link}" class="btn" target="_blank" rel="noopener noreferrer" aria-label="خرید طلا از ${p.name} (در پنجره جدید)">خرید طلا</a>
                </td>
            </tr>
        `).join('');
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

    const data = await fetchData();
    if (data) {
        document.getElementById('current-date').textContent = data.meta.date;
        populateSummary(data.summary);
        populatePlatforms(data.platforms);
        populateCoins(data.coins);
    } else {
        const banner = document.getElementById('error-banner');
        if (banner) banner.classList.remove('d-none');
        // Hide skeletons if error
        document.querySelectorAll('.skeleton').forEach(el => el.classList.remove('skeleton'));
    }
});
