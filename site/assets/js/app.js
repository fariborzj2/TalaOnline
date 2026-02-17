document.addEventListener('DOMContentLoaded', async function() {
    const persianNumberFormatter = new Intl.NumberFormat('fa-IR');
    const toPersianDigits = (num) => {
        if (num === null || num === undefined) return '';
        return persianNumberFormatter.format(num);
    };

    const formatPrice = (price) => toPersianDigits(price);

    const getAssetUrl = (path) => {
        if (!path) return '/assets/images/gold/gold.webp';
        if (path.startsWith('http')) return path;
        let clean = path.startsWith('/') ? path : '/' + path;
        return clean.replace(/\.(png|jpg|jpeg)$/i, '.webp');
    };

    const populatePlatforms = (platforms) => {
        const list = document.getElementById('platforms-list');
        if (!list) return;

        // Calculate best buy/sell
        let minBuy = null;
        let maxSell = null;
        platforms.forEach(p => {
            const buy = parseFloat(p.buy_price || 0);
            const sell = parseFloat(p.sell_price || 0);
            const fee = parseFloat(p.fee || 0);
            const effBuy = buy * (1 + fee / 100);
            const effSell = sell * (1 - fee / 100);
            if (minBuy === null || effBuy < minBuy) minBuy = effBuy;
            if (maxSell === null || effSell > maxSell) maxSell = effSell;
        });

        list.innerHTML = platforms.map(platform => {
            const buy = parseFloat(platform.buy_price || 0);
            const sell = parseFloat(platform.sell_price || 0);
            const fee = parseFloat(platform.fee || 0);
            const effBuy = buy * (1 + fee / 100);
            const effSell = sell * (1 - fee / 100);

            let statusText = 'عادی';
            let statusClass = 'warning';

            if (minBuy !== null && effBuy <= minBuy) {
                statusText = 'مناسب خرید';
                statusClass = 'success';
            } else if (maxSell !== null && effSell >= maxSell) {
                statusText = 'مناسب فروش';
                statusClass = 'info';
            }

            return `
                <tr>
                    <td>
                        <div class="brand-logo"> <img src="${getAssetUrl(platform.logo)}" alt="${platform.name}" loading="lazy" decoding="async" width="32" height="32"> </div>
                    </td>
                    <td>
                        <div class="line20">
                            <div class="text-title">${platform.name}</div>
                            <div class="font-size-0-8">${platform.en_name || ''}</div>
                        </div>
                    </td>
                    <td class="font-size-2 font-bold text-title">${formatPrice(platform.buy_price)}</td>
                    <td class="font-size-2 font-bold text-title">${formatPrice(platform.sell_price)}</td>
                    <td class="font-size-2 " dir="ltr">${toPersianDigits(platform.fee)}%</td>
                    <td>
                        <span class="status-badge ${statusClass}">
                            ${statusText}
                        </span>
                    </td>
                    <td>
                        <a href="${platform.link}" class="btn btn-secondary btn-sm" target="_blank" rel="noopener noreferrer">
                            <i data-lucide="external-link" class="h-4 w-4"></i> خرید طلا
                        </a>
                    </td>
                </tr>
            `;
        }).join('');
        if (window.lucide) window.lucide.createIcons({ root: list });
    };

    const initSearch = (platforms) => {
        const searchInput = document.getElementById('platform-search');
        if (!searchInput) return;
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            const filtered = platforms.filter(p =>
                p.name.toLowerCase().includes(query) ||
                (p.en_name && p.en_name.toLowerCase().includes(query))
            );
            populatePlatforms(filtered);
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

            const tocPlaceholder = area.querySelector('#toc-placeholder') || document.getElementById('toc-container');
            const headings = area.querySelectorAll('h2, h3');
            if (tocPlaceholder && headings.length > 1) {
                const isSidebar = tocPlaceholder.id === 'toc-container';
                const tocList = document.createElement('ul');
                tocList.className = isSidebar ? 'd-column gap-02 mt-1' : 'toc-list';

                headings.forEach((heading, index) => {
                    const id = `heading-${index}`;
                    heading.id = id;
                    const li = document.createElement('li');
                    const a = document.createElement('a');
                    a.href = `#${id}`;
                    a.textContent = heading.textContent;
                    a.className = isSidebar ? 'toc-item' : '';
                    if (heading.tagName.toLowerCase() === 'h3' && isSidebar) a.style.paddingRight = '20px';

                    a.onclick = (e) => {
                        e.preventDefault();
                        heading.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        if (isSidebar) {
                            tocList.querySelectorAll('a').forEach(el => el.classList.remove('active'));
                            a.classList.add('active');
                        }
                    };
                    li.appendChild(a);
                    tocList.appendChild(li);
                });
                tocPlaceholder.appendChild(tocList);
                if (!isSidebar) {
                    tocPlaceholder.classList.add('toc-container');
                    tocPlaceholder.insertAdjacentHTML('afterbegin', '<div class="toc-title"><i data-lucide="list"></i> فهرست مطالب</div>');
                }
                if (window.lucide) window.lucide.createIcons({ attrs: { 'data-lucide': true }, root: tocPlaceholder });
            }
        });
    };

    if (window.__INITIAL_STATE__ && window.__INITIAL_STATE__.platforms) {
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
