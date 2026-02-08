<div class="block-card order-0 fade-in-up" style="animation-delay: 0.4s;">
    <div id="coins-list">
        <?php foreach ($coins as $item): ?>
            <?= View::renderComponent('coin_item', ['item' => $item]) ?>
        <?php endforeach; ?>
    </div>
</div>
