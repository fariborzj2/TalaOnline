<div class="section">
    <?= View::renderSection('coins', [
        'coins' => $items,
        'title' => $category['name'],
        'subtitle' => $category['en_name'],
        'icon' => $category['icon'] ?? 'coins',
        'id' => 'category-market-list'
    ]) ?>
</div>

<?php if (!empty($category['description'])): ?>
<div class="section">
    <div class="bg-block border radius-16 pd-lg">
        <div class="d-flex align-center gap-1 mb-1">
            <div class="w-10 h-10 border radius-12 p-05 bg-secondary d-flex align-center just-center">
                <i data-lucide="info" color="var(--color-primary)" class="w-6 h-6"></i>
            </div>
            <h2 class="font-size-2">توضیحات و راهنما</h2>
        </div>
        <div class="content-text line-height-2 text-gray">
            <?= $category['description'] ?>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    window.__INITIAL_STATE__ = {
        category: <?= json_encode($category) ?>,
        items: <?= json_encode($items) ?>
    };
</script>
