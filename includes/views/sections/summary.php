<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
    <?= View::renderComponent('price_card', [
        'id' => 'gold-summary',
        'title' => 'طلای ۱۸ عیار',
        'symbol' => 'Gold',
        'price' => $gold_data['price'] ?? 0,
        'change' => $gold_data['change_percent'] ?? 0,
        'color' => 'bg-blue-500',
    ]) ?>

    <?= View::renderComponent('price_card', [
        'id' => 'silver-summary',
        'title' => 'نقره ۹۹۹',
        'symbol' => 'Silver',
        'price' => $silver_data['price'] ?? 0,
        'change' => $silver_data['change_percent'] ?? 0,
        'color' => 'bg-purple-500',
    ]) ?>

    <?php foreach ($coins as $coin): ?>
    <?= View::renderComponent('price_card', [
        'title' => $coin['name'],
        'symbol' => $coin['symbol'],
        'price' => $coin['price'],
        'change' => $coin['change_percent'],
        'color' => 'bg-indigo-500',
    ]) ?>
    <?php endforeach; ?>
</div>
