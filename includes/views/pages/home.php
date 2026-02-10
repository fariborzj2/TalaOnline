<div class="section">
    <?php
    // Prepare a list of items for the summary (e.g. first 3 items that are not gold/silver)
    $summary_items = [];
    foreach ($grouped_items as $group) {
        foreach ($group['items'] as $item) {
            if ($item['symbol'] !== '18ayar' && $item['symbol'] !== 'silver' && count($summary_items) < 3) {
                $summary_items[] = $item;
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
                'title' => $group['info']['name'],
                'subtitle' => $group['info']['en_name'],
                'icon' => $group['info']['icon'] ?? 'coins',
                'id' => $category_slug . '-market-list'
            ]) ?>
        <?php endforeach; ?>
    </div>
</div>

<div class="section">
    <?= View::renderSection('chart', [
        'gold_data' => $gold_data
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
