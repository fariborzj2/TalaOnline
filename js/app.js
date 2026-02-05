document.addEventListener('DOMContentLoaded', async function() {
    console.log('App script initialized');

    const toPersianDigits = (str) => {
        if (str === null || str === undefined) return '';
        const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return str.toString().replace(/\d/g, x => persianDigits[x]);
    };

    const formatPrice = (price) => {
        return toPersianDigits(price.toLocaleString());
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

    const populateSummary = (summary) => {
        const assets = ['gold', 'silver'];
        assets.forEach(asset => {
            const container = document.getElementById(`${asset}-summary`);
            if (!container) return;

            const data = summary[asset];
            container.querySelector('.current-price').textContent = formatPrice(data.current);
            container.querySelector('.price-change').textContent = formatPrice(data.change);

            const percentEl = container.querySelector('.change-percent');
            percentEl.textContent = toPersianDigits(data.change_percent) + '٪';
            percentEl.className = data.change >= 0 ? 'color-green change-percent' : 'color-red change-percent';

            container.querySelector('.high-price').textContent = formatPrice(data.high);
            container.querySelector('.low-price').textContent = formatPrice(data.low);
        });

        // Also update the chart high/low placeholders if needed,
        // but charts.js might handle its own.
        // For now let's sync them with the dashboard summary or let charts.js do its thing.
        document.querySelector('.chart-high-price').textContent = formatPrice(summary.gold.high);
        document.querySelector('.chart-low-price').textContent = formatPrice(summary.gold.low);
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
                    <a href="${p.link}" class="btn" aria-label="خرید طلا از ${p.name}">خرید طلا</a>
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
                    <div class="color-green">${toPersianDigits(c.change_percent)}%</div>
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
    }
});
