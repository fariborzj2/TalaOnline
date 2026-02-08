<div class="mob-scroll basis500 grow-1 d-flex gap-20">
    <?= View::renderComponent('price_card', [
        'id' => 'gold-summary',
        'title' => 'طلای ۱۸ عیار',
        'subtitle' => 'قیمت لحظه‌ای طلا',
        'icon' => 'gold',
        'icon_class' => 'gold-icon',
        'data' => $gold_data,
        'delay' => 0
    ]) ?>

    <?= View::renderComponent('price_card', [
        'id' => 'silver-summary',
        'title' => 'نقره ۹۹۹',
        'subtitle' => 'قیمت لحظه‌ای نقره',
        'icon' => 'silver',
        'icon_class' => 'silver-icon',
        'data' => $silver_data,
        'delay' => 0.1
    ]) ?>
</div>
