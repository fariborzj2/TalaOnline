<div class="section">
    <?= View::renderSection('summary', [
        'gold_data' => $gold_data,
        'silver_data' => $silver_data,
        'coins' => array_slice($coins, 0, 3)
    ]) ?>
</div>

<div class="section">
    <div class="d-flex-wrap gap-md">
        <!-- Gold and Coins -->
        <?= View::renderSection('coins', [
            'coins' => $coins,
            'title' => 'بازار طلا و سکه',
            'subtitle' => 'gold market',
            'id' => 'gold-market-list'
        ]) ?>

        <!-- You could add another coins section here for Currency if needed,
             but for now let's just use what we have in index.html as a placeholder or second list -->
        <?php
        // Just as an example of how to match the layout
        echo View::renderSection('coins', [
            'coins' => array_slice($coins, 4, 4),
            'title' => 'حباب طلا و سکه',
            'subtitle' => 'gold bubble',
            'id' => 'bubble-market-list'
        ]);
        ?>
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
        coins: <?= json_encode($coins) ?>,
        summary: {
            gold: <?= json_encode($gold_data) ?>,
            silver: <?= json_encode($silver_data) ?>
        }
    };
</script>
