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
                <a href="<?= htmlspecialchars($item['link']) ?>" target="_blank" class="news-item d-flex gap-1 text-decoration-none group align-start">
                    <?php if (!empty($item['image'])): ?>
                        <div class="news-image radius-8 overflow-hidden flex-shrink-0">
                            <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="w-full h-full object-cover">
                        </div>
                    <?php endif; ?>
                    <div class="d-column gap-025 flex-1">
                        <span class="d-flex text-gray font-size-0-8 gap-08"><span><?= htmlspecialchars($item['source']) ?></span> • <span><?= jalali_date($item['pubDate']) ?></span></span>
                        <h4 class="font-size-1 text-title line-height-1-5 ellipsis-x"><?= htmlspecialchars($item['title']) ?></h4>
                    </div>
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
    .news-image {
        width: 64px;
        height: 64px;
        background: var(--secondary-color);
    }
    .news-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .opacity-05 {
        opacity: 0.5;
    }
    .gap-025 {
        gap: 2px;
    }
    .object-cover {
        object-fit: cover;
    }
</style>
