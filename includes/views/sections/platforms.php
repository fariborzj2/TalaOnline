<div class="block-card fade-in-up" style="animation-delay: 0.3s;">
    <div class="d-flex-wrap just-between align-center mb-20 gap-15">
        <div class="d-flex gap-10 align-center">
            <div class="height-40 width-40 radius-100 border d-flex just-center align-center"><img src="assets/images/road-wayside.svg" alt="مقایسه پلتفرم‌ها"></div>
            <div>
                <h2 class="color-title">مقایسه پلتفرم های طلا آنلاین</h2>
                <span class="font-size-0-9">با بررسی قیمت ها بهترین پتلفرم را برای معامله انتخاب کنید</span>
            </div>
        </div>
        <div class="search-box" role="search">
            <input type="text" id="platform-search" placeholder="جستجوی پلتفرم..." class="search-input" aria-label="جستجوی نام پلتفرم طلا">
            <span class="search-icon-wrapper" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            </span>
            <div id="search-announcement" class="sr-only" aria-live="polite"></div>
        </div>
    </div>
    <div class="table-box">
        <table class="full-width">
            <thead>
                <tr>
                    <th>لوگو</th>
                    <th class="sortable" data-sort="name">
                        <div class="d-flex align-center">نام پلتفرم <span class="sort-icon"></span></div>
                    </th>
                    <th class="sortable" data-sort="buy_price">
                        <div class="d-flex align-center">قیمت خرید (تومان) <span class="sort-icon"></span></div>
                    </th>
                    <th class="sortable" data-sort="sell_price">
                        <div class="d-flex align-center">قیمت فروش (تومان) <span class="sort-icon"></span></div>
                    </th>
                    <th class="sortable" data-sort="fee">
                        <div class="d-flex align-center">کارمزد <span class="sort-icon"></span></div>
                    </th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody id="platforms-table-body">
                <?php foreach ($platforms as $p): ?>
                <tr>
                    <td>
                        <div class="brand-logo">
                            <img src="<?= htmlspecialchars($p['logo']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                        </div>
                    </td>
                    <td>
                        <div class="line20">
                            <div class="color-title"><?= htmlspecialchars($p['name']) ?></div>
                            <div class="font-size-0-8"><?= htmlspecialchars($p['en_name']) ?></div>
                        </div>
                    </td>
                    <td class="font-size-1-2 color-title"><?= fa_price($p['buy_price']) ?></td>
                    <td class="font-size-1-2 color-title"><?= fa_price($p['sell_price']) ?></td>
                    <td class="font-size-1-2" dir="ltr"><?= fa_num($p['fee']) ?>٪</td>
                    <td>
                        <span class="status-badge <?= $p['status'] === 'مناسب خرید' ? 'buy' : 'sell' ?>">
                            <?= htmlspecialchars($p['status']) ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?= htmlspecialchars($p['link']) ?>" class="btn" target="_blank" rel="noopener noreferrer" aria-label="خرید طلا از <?= htmlspecialchars($p['name']) ?> (در پنجره جدید)">خرید طلا</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
