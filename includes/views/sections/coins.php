<div class="bg-block border basis-350 grow-1 radius-16 nowrap">
    <div class="d-flex just-between align-center gap-1 pd-md border-bottom">
        <div class="d-flex align-center gap-1">
            <div class="w-10 h-10 border radius-12 p-05 bg-secondary d-flex align-center just-center">
                <i data-lucide="<?= $icon ?? 'coins' ?>" color="var(--color-primary)" class="w-6 h-6"></i>
            </div>
            <div class="line-height-1-5">
                <h2 class="font-size-2"><?= $title ?? 'بازار طلا و سکه' ?></h2>
                <span class="text-gray"><?= $subtitle ?? 'gold market' ?></span>
            </div>
        </div>
        <?php if (isset($slug)): ?>
        <a href="/<?= $slug ?>" class="btn btn-secondary btn-sm radius-10">
            <span class="font-size-1">مشاهده همه</span>
            <i data-lucide="chevron-left" class="icon-size-2"></i>
        </a>
        <?php endif; ?>
    </div>

    <div class="p-1 d-column" id="<?= $id ?? 'coins-list' ?>">
        <?php foreach ($coins as $coin): ?>
            <?= View::renderComponent('coin_item', [
                'coin' => $coin,
                'image' => ($coin['logo'] ?? '') ?: 'assets/images/gold/' . (strpos($coin['name'] ?? '', 'نیم') !== false ? 'nim' : (strpos($coin['name'] ?? '', 'ربع') !== false ? 'rob' : 'gold')) . '.png'
            ]) ?>
        <?php endforeach; ?>
    </div>
</div>
