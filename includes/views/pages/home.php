<div class="section">
    <?php
    // If no manual summary items are selected, use fallback logic
    if (empty($summary_items)) {
        if ($gold_data) $summary_items[] = $gold_data;
        if ($silver_data) $summary_items[] = $silver_data;

        foreach ($grouped_items as $group) {
            foreach ($group['items'] as $item) {
                if ($item['symbol'] !== '18ayar' && $item['symbol'] !== 'silver' && count($summary_items) < 4) {
                    $summary_items[] = $item;
                }
            }
        }
    }
    ?>
    <?= View::renderSection('summary', [
        'gold_data' => $gold_data,
        'silver_data' => $silver_data,
        'coins' => $summary_items
    ]) ?>
</div>

<div class="section">
    <div class="d-flex-wrap gap-md">
        <?php foreach ($grouped_items as $category_slug => $group): ?>
            <?php if (empty($group['items'])) continue; ?>
            <?= View::renderSection('coins', [
                'coins' => $group['items'],
                'title' => $group['info']['name'] ?? '',
                'subtitle' => $group['info']['en_name'] ?? '',
                'icon' => $group['info']['icon'] ?? 'coins',
                'slug' => $category_slug,
                'id' => $category_slug . '-market-list'
            ]) ?>
        <?php endforeach; ?>
    </div>
</div>

<div class="section">
    <?= View::renderSection('chart', [
        'gold_data' => $gold_data,
        'chart_items' => $chart_items
    ]) ?>
</div>

<div class="section">
    <?= View::renderSection('platforms', [
        'platforms' => $platforms
    ]) ?>
</div>

<script>
    window.__INITIAL_STATE__ = {
        platforms: <?= json_encode($platforms) ?>,
        grouped_items: <?= json_encode($grouped_items) ?>,
        summary: {
            gold: <?= json_encode($gold_data) ?>,
            silver: <?= json_encode($silver_data) ?>
        }
    };
</script>
