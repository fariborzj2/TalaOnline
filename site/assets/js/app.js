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

    // --- Modal Logic ---
    const modal = document.getElementById('detail-modal');
    const closeModal = document.getElementById('close-modal');
    let modalChartInstance = null;

    const openModal = async (assetData) => {
        if (!modal) return;

        // Populate fields
        document.getElementById('modal-title').textContent = assetData.name;
        document.getElementById('modal-symbol').textContent = assetData.symbol;
        document.getElementById('modal-price').textContent = formatPrice(assetData.price);
        document.getElementById('modal-asset-icon').src = assetData.image;

        const isPos = assetData.change >= 0;
        const changePercentEl = document.getElementById('modal-change-percent');

        // Reset HTML to ensure we have a fresh <i> tag for Lucide to process
        changePercentEl.innerHTML = `
            <span>${toPersianDigits(Math.abs(assetData.change))}%</span>
            <i data-lucide="${isPos ? 'arrow-up' : 'arrow-down'}"></i>
        `;
        changePercentEl.className = `d-flex align-center gap-05 font-bold ${isPos ? 'text-success' : 'text-error'}`;

        document.getElementById('modal-change-amount').textContent = (isPos ? '+ ' : '- ') + formatPrice(Math.abs(assetData.change_amount));
        document.getElementById('modal-high').textContent = formatPrice(assetData.high);
        document.getElementById('modal-low').textContent = formatPrice(assetData.low);

        lucide.createIcons();
        modal.classList.remove('d-none');

        // Initialize Chart
        if (window.AssetChart) {
            modalChartInstance = new window.AssetChart('#modal-chart', assetData.symbol);
            if (await modalChartInstance.fetchData()) {
                modalChartInstance.render();
            }
        }
    };

    if (closeModal) {
        closeModal.addEventListener('click', () => {
            modal.classList.add('d-none');
            if (modalChartInstance && modalChartInstance.chart) {
                modalChartInstance.chart.destroy();
                modalChartInstance = null;
            }
        });
    }

    // Period toggle for modal
    document.querySelectorAll('#modal-period-toggle button').forEach(btn => {
        btn.addEventListener('click', function() {
            this.parentElement.querySelectorAll('button').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const period = this.getAttribute('data-period');
            const days = period === '7d' ? 7 : (period === '30d' ? 30 : 365);
            if (modalChartInstance) {
                modalChartInstance.updatePeriod(days);
            }
        });
    });

    // Delegate click events for assets
    document.addEventListener('click', (e) => {
        const assetItem = e.target.closest('.asset-item');
        if (assetItem) {
            try {
                const data = JSON.parse(assetItem.getAttribute('data-asset'));
                openModal(data);
            } catch (err) {
                console.error('Error parsing asset data:', err);
            }
        }
    });

    // --- Search & Dashboard Data ---
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

    const populatePlatforms = (platforms) => {
        const tbody = document.getElementById('platforms-list');
        if (!tbody) return;

        if (platforms.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="pd-md text-center text-gray">موردی یافت نشد.</td></tr>`;
            return;
        }

        tbody.innerHTML = platforms.map(p => `
            <tr>
                <td>
                    <div class="brand-logo"> <img src="${p.logo}" alt="${p.name}"> </div>
                </td>
                <td>
                    <div class="line20">
                        <div class="text-title">${p.name}</div>
                        <div class="font-size-0-8">${p.en_name || ''}</div>
                    </div>
                </td>
                <td class="font-size-2 font-bold text-title">${formatPrice(p.buy_price)}</td>
                <td class="font-size-2 font-bold text-title">${formatPrice(p.sell_price)}</td>
                <td class="font-size-2 " dir="ltr">${toPersianDigits(p.fee)}%</td>
                <td>
                    <span class="status-badge ${p.status === 'active' || p.status === 'مناسب خرید' ? 'success' : 'warning'}">
                        ${p.status === 'active' ? 'مناسب خرید' : (p.status || 'نامشخص')}
                    </span>
                </td>
                <td>
                    <a href="${p.link}" class="btn btn-secondary btn-sm" target="_blank" rel="noopener noreferrer">
                        <i data-lucide="external-link" class="h-4 w-4"></i> خرید طلا
                    </a>
                </td>
            </tr>
        `).join('');
        lucide.createIcons();
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

    const initApp = async () => {
        if (window.__INITIAL_STATE__) {
            const data = window.__INITIAL_STATE__;
            state.platforms = data.platforms;
            initSearch();
        } else {
            const data = await fetchData();
            if (data) {
                state.platforms = data.platforms;
                populatePlatforms(state.platforms);
                initSearch();
            }
        }
    };

    await initApp();
});
