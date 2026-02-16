document.addEventListener('DOMContentLoaded', async function() {
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
        document.getElementById('modal-title').textContent = assetData.name;
        document.getElementById('modal-symbol').textContent = assetData.symbol;
        document.getElementById('modal-price').textContent = formatPrice(assetData.price);
        document.getElementById('modal-asset-icon').src = assetData.image;

        const isPos = assetData.change >= 0;
        const changePercentEl = document.getElementById('modal-change-percent');
        changePercentEl.innerHTML = `<span>${toPersianDigits(Math.abs(assetData.change))}%</span><i data-lucide="${isPos ? 'arrow-up' : 'arrow-down'}" class="icon-size-1"></i>`;
        changePercentEl.className = `d-flex align-center gap-05 font-bold ${isPos ? 'text-success' : 'text-error'}`;
        document.getElementById('modal-change-amount').textContent = (isPos ? '+ ' : '- ') + formatPrice(Math.abs(assetData.change_amount));
        document.getElementById('modal-high').textContent = formatPrice(assetData.high);
        document.getElementById('modal-low').textContent = formatPrice(assetData.low);

        const detailsLink = document.getElementById('modal-details-link');
        if (detailsLink) detailsLink.href = (assetData.category ? '/' + assetData.category : '') + '/' + assetData.slug;

        if (window.lucide) window.lucide.createIcons({ attrs: { 'data-lucide': true }, root: modal });
        modal.classList.remove('d-none');

        if (window.AssetChart) {
            modalChartInstance = new window.AssetChart('#modal-chart', assetData.symbol);
            if (await modalChartInstance.fetchData()) modalChartInstance.render();
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

    document.querySelectorAll('#modal-period-toggle button').forEach(btn => {
        btn.addEventListener('click', function() {
            this.parentElement.querySelectorAll('button').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const period = this.getAttribute('data-period');
            const days = period === '7d' ? 7 : (period === '30d' ? 30 : 365);
            if (modalChartInstance) modalChartInstance.updatePeriod(days);
        });
    });

    document.addEventListener('click', (e) => {
        const assetItem = e.target.closest('.asset-item');
        if (assetItem) {
            try {
                openModal(JSON.parse(assetItem.getAttribute('data-asset')));
            } catch (err) { console.error(err); }
        }
    });

    const initSearch = (platforms) => {
        const searchInput = document.getElementById('platform-search');
        if (!searchInput) return;
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            const filtered = platforms.filter(p => p.name.toLowerCase().includes(query) || (p.en_name && p.en_name.toLowerCase().includes(query)));
            // Note: populatePlatforms logic would go here if platforms list needs dynamic filtering
        });
    };

    const enhanceContent = () => {
        document.querySelectorAll('.content-text').forEach(area => {
            area.querySelectorAll('table').forEach(table => {
                if (!table.parentElement.classList.contains('table-wrapper')) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'table-wrapper';
                    table.parentNode.insertBefore(wrapper, table);
                    wrapper.appendChild(table);
                }
            });

            const tocPlaceholder = area.querySelector('#toc-placeholder');
            const headings = area.querySelectorAll('h2, h3');
            if (tocPlaceholder && headings.length > 1) {
                const tocContainer = document.createElement('div');
                tocContainer.className = 'toc-container';
                tocContainer.innerHTML = '<div class="toc-title"><i data-lucide="list"></i> فهرست مطالب</div>';
                const tocList = document.createElement('ul');
                tocList.className = 'toc-list';
                headings.forEach((heading, index) => {
                    const id = `heading-${index}`;
                    heading.id = id;
                    const li = document.createElement('li');
                    li.className = `toc-${heading.tagName.toLowerCase()}`;
                    const a = document.createElement('a');
                    a.href = `#${id}`;
                    a.textContent = heading.textContent;
                    a.onclick = (e) => { e.preventDefault(); heading.scrollIntoView({ behavior: 'smooth' }); };
                    li.appendChild(a);
                    tocList.appendChild(li);
                });
                tocContainer.appendChild(tocList);
                tocPlaceholder.appendChild(tocContainer);
                if (window.lucide) window.lucide.createIcons({ attrs: { 'data-lucide': true }, root: tocContainer });
            }
        });
    };

    if (window.__INITIAL_STATE__) {
        initSearch(window.__INITIAL_STATE__.platforms);
    } else {
        try {
            const response = await fetch('/api/dashboard.php');
            const data = await response.json();
            if (data && data.platforms) initSearch(data.platforms);
        } catch (e) {}
    }

    enhanceContent();
    if (window.lucide) window.lucide.createIcons();
    document.dispatchEvent(new CustomEvent('app:content-ready'));
});
