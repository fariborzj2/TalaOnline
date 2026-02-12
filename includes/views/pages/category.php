<?php if (!empty($category['short_description'])): ?>
<div class="section">
    <div class="bg-block border radius-16 pd-md">
        <div class="content-text line-height-2">
            <?= $category['short_description'] ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="section">
    <?= View::renderSection('coins', [
        'coins' => $items,
        'title' => $category['name'],
        'subtitle' => $category['en_name'],
        'icon' => $category['icon'] ?? 'coins',
        'id' => 'category-market-list'
    ]) ?>
</div>

<?php if (!empty($category['description'])): ?>
<div class="section">
    <div class="bg-block pd-md border radius-16">
        <div class="d-flex align-center gap-1 pb-1 mb-2 border-bottom">
            <div class="w-10 h-10 border radius-12 p-05 bg-secondary d-flex align-center just-center">
                <i data-lucide="info" color="var(--color-primary)" class="w-6 h-6"></i>
            </div>
            <h2 class="font-size-2">توضیحات و راهنما</h2>
        </div>
        <div class="content-text line-height-2">
            <?= $category['description'] ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($faqs)): ?>
<div class="section">
    <div class="bg-block pd-md border radius-16">
        <div class="d-flex align-center gap-1 pb-1 mb-2 border-bottom">
            <div class="w-10 h-10 border radius-12 p-05 bg-secondary d-flex align-center just-center">
                <i data-lucide="help-circle" color="var(--color-primary)" class="w-6 h-6"></i>
            </div>
            <h2 class="font-size-2">سوالات متداول</h2>
        </div>

        <div class="faq-list d-column gap-1">
            <?php foreach ($faqs as $faq): ?>
                <div class="faq-item border radius-12 overflow-hidden">
                    <div class="faq-question pd-md bg-secondary cursor-pointer d-flex just-between align-center" onclick="toggleFaq(this)">
                        <strong class="font-size-1 text-title"><?= htmlspecialchars($faq['question']) ?></strong>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-all"></i>
                    </div>
                    <div class="faq-answer pd-md border-top d-none">
                        <p class="font-size-1-2 line-height-2"><?= nl2br(htmlspecialchars($faq['answer'])) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    function toggleFaq(el) {
        const answer = el.nextElementSibling;
        const icon = el.querySelector('i');
        const isOpen = !answer.classList.contains('d-none');

        // Close all others
        document.querySelectorAll('.faq-answer').forEach(a => a.classList.add('d-none'));
        document.querySelectorAll('.faq-question i').forEach(i => i.style.transform = 'rotate(0deg)');

        if (!isOpen) {
            answer.classList.remove('d-none');
            icon.style.transform = 'rotate(180deg)';
        }
    }
</script>
<?php endif; ?>

<script>
    window.__INITIAL_STATE__ = {
        category: <?= json_encode($category) ?>,
        items: <?= json_encode($items) ?>
    };
</script>
