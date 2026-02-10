<div class="scrollbar-hidden d-flex gap-md">
    <?php if (empty($coins)): ?>
        <?= View::renderComponent('price_card', [
            'id' => 'gold-summary',
            'title' => 'طلای ۱۸ عیار',
            'symbol' => 'GOLD18',
            'price' => $gold_data['price'] ?? 0,
            'change' => $gold_data['change_percent'] ?? 0,
            'change_amount' => $gold_data['change_amount'] ?? 0,
            'image' => 'assets/images/gold/gold.png'
        ]) ?>

        <?= View::renderComponent('price_card', [
            'id' => 'silver-summary',
            'title' => 'نقره ۹۹۹',
            'symbol' => 'SILVER',
            'price' => $silver_data['price'] ?? 0,
            'change' => $silver_data['change_percent'] ?? 0,
            'change_amount' => $silver_data['change_amount'] ?? 0,
            'image' => 'assets/images/gold/gold.png'
        ]) ?>
    <?php else: ?>
        <?php foreach ($coins as $coin): ?>
        <?= View::renderComponent('price_card', [
            'title' => $coin['name'],
            'symbol' => strtoupper($coin['symbol']),
            'price' => $coin['price'],
            'change' => $coin['change_percent'],
            'change_amount' => $coin['change_amount'] ?? 0,
            'image' => $coin['logo'] ?: 'assets/images/gold/' . (strpos($coin['name'], 'نیم') !== false ? 'nim' : (strpos($coin['name'], 'ربع') !== false ? 'rob' : 'gold')) . '.png'
        ]) ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
