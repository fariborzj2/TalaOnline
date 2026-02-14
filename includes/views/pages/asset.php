<div class="section">
    <div class="bg-block pd-md border radius-16 d-flex-wrap align-center just-between gap-1 ">
        <div class="d-flex align-center gap-1">
            <div class="w-16 h-16 border radius-16 p-05 bg-white d-flex align-center just-center  shrink-0">
                <?php
                $logo_path = $item['logo'];
                if (empty($logo_path)) {
                    $logo_path = '/assets/images/gold/' . $item['symbol'] . '.png';
                }
                if (!str_starts_with($logo_path, '/') && !str_starts_with($logo_path, 'http')) {
                    $logo_path = '/' . $logo_path;
                }
                ?>
                <img src="<?= htmlspecialchars($logo_path) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="w-full h-full object-contain">
            </div>
            <div>
                <h1 class="font-size-2 font-black text-title"><?= htmlspecialchars($h1_title) ?></h1>
                <div class="d-flex align-center gap-05 mt-05">
                    <span class="text-gray font-size-0-9"><?= htmlspecialchars($item['en_name']) ?></span>
                    <span class="badge badge-secondary uppercase font-size-0-7"><?= htmlspecialchars($item['symbol']) ?></span>
                </div>
            </div>
        </div>

        <div class="d-flex d-column align-end">
            <div class="font-size-2-5 font-black text-title ltr">
                <?= fa_num(number_format($item['price'])) ?>
                <span class="font-size-1 text-gray">تومان</span>
            </div>
            <div class="d-flex align-center gap-05 mt-05" dir="ltr">
                <?php
                $change_val = $item['change_percent'] ?? 0;
                $change_class = $change_val >= 0 ? 'text-success' : 'text-error';
                $change_icon = $change_val >= 0 ? 'trending-up' : 'trending-down';
                ?>
                <span class="font-size-1 font-bold <?= $change_class ?> ltr d-flex align-center gap-025">
                    <i data-lucide="<?= $change_icon ?>" class="w-4 h-4"></i>
                    <?= $change_val >= 0 ? '+' : '' ?><?= fa_num(abs($change_val)) ?>%
                </span>
                <span class="font-size-0-9 text-gray ltr">
                    <?= fa_num(number_format(abs($item['change_amount'] ?? 0))) ?>
                </span>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($item['description'])): ?>
<div class="section">
    
    <div class="bg-block pd-md border radius-16  grow-1">
        <div class="d-flex align-center gap-05 mb-1 border-bottom pb-1">
            <i data-lucide="text-quote" class="w-5 h-5 text-primary"></i>
            <h3 class="font-size-1 font-black">درباره این دارایی</h3>
        </div>
        <p class="font-size-0-9 line-height-1-8 text-justify">
            <?= htmlspecialchars($item['description']) ?>
        </p>
    </div>
</div>
<?php endif; ?>

<div class="section">
    <div class="bg-block pd-md border radius-16">
        <div class="d-flex align-center gap-05 mb-1 border-bottom pb-1">
            <i data-lucide="info" class="w-5 h-5 text-primary"></i>
            <h3 class="font-size-1 font-black">اطلاعات تکمیلی</h3>
        </div>
        <div class="d-column gap-1">
            <div class="d-flex just-between align-center">
                <span class="text-gray font-size-0-9">بیشترین (۲۴ساعته):</span>
                <strong class="text-success ltr font-size-0-9"><?= fa_num(number_format($item['high'] ?? $item['price'])) ?></strong>
            </div>
            <div class="d-flex just-between align-center">
                <span class="text-gray font-size-0-9">کمترین (۲۴ساعته):</span>
                <strong class="text-error ltr font-size-0-9"><?= fa_num(number_format($item['low'] ?? $item['price'])) ?></strong>
            </div>
            <div class="d-flex just-between align-center border-top pt-1">
                <span class="text-gray font-size-0-9">آخرین بروزرسانی:</span>
                <span class="font-size-0-8 font-bold"><?= jalali_date() ?></span>
            </div>
        </div>
    </div>
</div>

<div class="section">
    <?= View::renderSection('chart', [
        'chart_items' => [$item],
        'title' => 'نمودار قیمت ' . $item['name'],
        'desc' => 'نوسانات قیمت ' . $item['name'] . ' در بازه‌های زمانی مختلف',
        'hide_stats' => true
    ]) ?>
</div>

<?php if (!empty($item['long_description'])): ?>
<div class="section">
    <div class="bg-block pd-md border radius-16 ">
        <div class="d-flex align-center gap-1 pb-1 mb-2 border-bottom">
            <div class="w-10 h-10 border radius-12 p-05 bg-secondary d-flex align-center just-center ">
                <i data-lucide="file-text" color="var(--color-primary)" class="w-6 h-6"></i>
            </div>
            <h2 class="font-size-2 font-black">تحلیل و بررسی <?= $item['name'] ?></h2>
        </div>
        <div class="content-text line-height-2 font-size-1-1">
            <div id="toc-placeholder"></div>
            <?= $item['long_description'] ?>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    window.__INITIAL_STATE__ = {
        item: <?= json_encode($item) ?>,
        grouped_items: {
            "<?= $item['category'] ?>": {
                items: [<?= json_encode($item) ?>]
            }
        },
        summary: {
            gold: <?= ($item['symbol'] == '18ayar') ? json_encode($item) : 'null' ?>,
            silver: <?= ($item['symbol'] == 'silver') ? json_encode($item) : 'null' ?>
        }
    };
</script>
