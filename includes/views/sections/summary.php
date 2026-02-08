<div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full lg:w-1/2">
    <?= View::renderComponent('price_card', [
        'id' => 'gold-summary',
        'title' => 'طلای ۱۸ عیار',
        'subtitle' => 'قیمت لحظه‌ای هر گرم طلا',
        'icon' => 'gold',
        'icon_class' => 'gold-icon',
        'data' => $gold_data,
        'delay' => 0
    ]) ?>

    <?= View::renderComponent('price_card', [
        'id' => 'silver-summary',
        'title' => 'نقره ۹۹۹',
        'subtitle' => 'قیمت لحظه‌ای هر گرم نقره',
        'icon' => 'silver',
        'icon_class' => 'silver-icon',
        'data' => $silver_data,
        'delay' => 0.1
    ]) ?>
</div>
