<div class="blog-container d-column gap-md">
    <div class="d-flex-wrap gap-md">
        <?php foreach ($tags as $tag): ?>
        <a href="/blog/tags/<?= urlencode($tag['slug']) ?>" class="tag-cloud-item d-column align-center gap-05 pd-md radius-16 border transition-all">
            <div class="tag-icon-circle d-flex align-center just-center radius-50">
                <i data-lucide="hash" class="w-5 h-5"></i>
            </div>
            <span class="font-bold text-title"><?= htmlspecialchars($tag['name']) ?></span>
            <span class="text-subtitle font-size-1 opacity-50"><?= number_format($tag['post_count']) ?> مقاله</span>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($tags)): ?>
        <div class="bg-block border radius-20 pd-md">
            <div class="basis-100 text-center pd-xl opacity-50">
                <i data-lucide="hash" class="w-16 h-16 mb-1 opacity-10 mg-auto"></i>
                <p class="font-bold">هنوز برچسبی ایجاد نشده است.</p>
            </div>
        </div>
    <?php endif; ?>
    
</div>

<style>
    .tag-cloud-item {
        background: var(--color-block);
        color: var(--color-text);
        min-width: 140px;
        flex: 1 1 140px;
        max-width: 200px;
        text-align: center;
    }
    .tag-icon-circle {
        width: 48px;
        height: 48px;
        background: var(--color-primary-light);
        color: var(--color-primary);
        transition: all 0.3s;
        border-radius: 12px
    }
    .tag-cloud-item:hover .tag-icon-circle {
        background: var(--color-primary);
        color: white;
    }
    .pd-xl { padding: 3rem; }

    @media (max-width: 768px) {
        .tag-cloud-item { min-width: 120px; flex: 1 1 120px; }
        .pd-xl { padding: 1.5rem; }
    }
</style>
