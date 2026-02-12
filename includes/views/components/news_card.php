<div class="card bg-block radius-16 pd-md border d-column gap-1">
    <div class="d-flex align-center just-between mb-05">
        <div class="d-flex align-center gap-05">
            <i data-lucide="newspaper" class="text-primary icon-size-4"></i>
            <h3 class="font-size-2 font-bold">اخبار روز</h3>
        </div>
        <span class="pulse-container">
            <span class="pulse-dot"></span>
        </span>
    </div>

    <div class="news-list d-column gap-1">
        <?php if (empty($news)): ?>
            <p class="text-gray font-size-1 text-center py-1">خبری یافت نشد.</p>
        <?php else: ?>
            <?php foreach ($news as $index => $item): ?>
                <a href="<?= htmlspecialchars($item['link']) ?>" target="_blank" class="news-item d-column gap-025 text-decoration-none group">
                    <span class="text-gray font-size-0-8 font-bold"><?= htmlspecialchars($item['source']) ?> • <?= jalali_date($item['pubDate']) ?></span>
                    <h4 class="font-size-1-1 text-title line-height-1-4 group-hover-text-primary transition-all"><?= htmlspecialchars($item['title']) ?></h4>
                </a>
                <?php if ($index < count($news) - 1): ?>
                    <div class="border-bottom opacity-05"></div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
    .news-item {
        transition: all 0.2s;
    }
    .news-item:hover h4 {
        color: var(--color-primary);
    }
    .opacity-05 {
        opacity: 0.5;
    }
    .gap-025 {
        gap: 2px;
    }
</style>
