<div class="bg-block border radius-24 overflow-hidden">
    <div class="pd-md border-bottom d-flex just-between align-center">
        <h2 class="font-size-4 font-black text-title">عملکرد بازار</h2>
        <div class="d-flex gap-1">
             <button class="pd-sm border radius-12 text-gray hover:text-primary transition-all" aria-label="تنظیمات">
                <i data-lucide="sliders-horizontal" class="icon-size-4"></i>
             </button>
             <button class="pd-sm border radius-12 text-gray hover:text-primary transition-all" aria-label="بیشتر">
                <i data-lucide="more-horizontal" class="icon-size-4"></i>
             </button>
        </div>
    </div>

    <div class="table-box">
        <table class="w-full text-right border-collapse">
            <thead>
                <tr class="font-size-1 font-black text-gray uppercase">
                    <th class="pd-md">دارایی</th>
                    <th class="pd-md">قیمت (تومان)</th>
                    <th class="pd-md">تغییر ۲۴ ساعته</th>
                    <th class="pd-md">ارزش بازار</th>
                    <th class="pd-md">حجم (۲۴ ساعت)</th>
                    <th class="pd-md">عرضه در گردش</th>
                    <th class="pd-md">نمودار</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coins as $coin): ?>
                    <?= View::renderComponent('market_row', ['item' => $coin]) ?>
                <?php endforeach; ?>
                <?php foreach ($platforms as $platform): ?>
                    <?= View::renderComponent('market_row', ['item' => $platform, 'is_platform' => true]) ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
