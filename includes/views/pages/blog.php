<div class="blog-container d-column gap-md">

    <?php if (!empty($featured_posts) && !isset($current_category)): ?>
    <section class="featured-posts mb-2">
        <div class="d-flex align-center gap-1 mb-1 pr-1">
            <h2 class="font-size-4 font-bold">مقالات برگزیده</h2>
        </div>
        <div class="d-flex-wrap gap-md">
            <?php foreach ($featured_posts as $post): ?>
            <a href="/blog/<?= htmlspecialchars($post['category_slug'] ?? 'uncategorized') ?>/<?= htmlspecialchars($post['slug']) ?>" class="blog-card featured basis-300 grow-1 bg-block border radius-20 overflow-hidden d-column relative">
                
                <div class="p-2 d-column gap-1">
                    <div class="aspect-video relative overflow-hidden radius-16">
                        <?php if ($post['thumbnail']): ?>
                        <img src="/<?= ltrim($post['thumbnail'], '/') ?>"
                                alt="<?= htmlspecialchars($post['title']) ?>"
                                class="w-full h-full object-cover transition-all">
                        <?php else: ?>
                        <div class="w-full h-full d-flex align-center justify-center ">
                            <i data-lucide="image" class="w-16 h-16 opacity-20"></i>
                        </div>
                        <?php endif; ?>
                        <div class="absolute bottom-0 right-0 left-0 p-2 bg-gradient-to-t from-black/60 to-transparent">
                            <span class="glass-badge radius-20">
                                <?= htmlspecialchars($post['category_name'] ?? 'وبلاگ') ?>
                            </span>
                        </div>
                    </div>

                    <h3 class="font-bold font-size-3 text-title line-clamp-2"><?= htmlspecialchars($post['title']) ?></h3>
                    <p class="line-clamp-2"><?= htmlspecialchars($post['excerpt']) ?></p>
                    <div class="d-flex just-between align-center mt-05 pt-1 border-top">
                        <span class=" text-subtitle d-flex align-center gap-05 font-bold">
                            <i data-lucide="calendar-days" class="icon-size-2 text-primary"></i>
                            <?= jalali_time_tag($post['created_at']) ?>
                        </span>
                        <div class="text-primary text-[11px] font-bold d-flex align-center gap-05">
                            مطالعه مقاله <i data-lucide="arrow-left" class="icon-size-2"></i>
                        </div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <div class="grow-8 d-column gap-md">
        <section class="posts-list">
            <div class="d-flex just-between align-center mb-1 pr-1">
                <div class="d-flex align-center gap-1">
                    <h2 class="font-size-3 font-bold"><?= isset($current_category) ? 'مقالات ' . htmlspecialchars($current_category['name']) : 'آخرین نوشته‌ها' ?></h2>
                </div>
                <?php if (isset($current_category)): ?>
                <a href="/blog" class="btn-blog-outline">مشاهده همه</a>
                <?php endif; ?>
            </div>

            <div class="d-column gap-md">
                <?php foreach ($posts as $post): ?>
                <a href="/blog/<?= htmlspecialchars($post['category_slug'] ?? 'uncategorized') ?>/<?= htmlspecialchars($post['slug']) ?>" class="blog-card d-flexx-wrap gap-md bg-block border radius-20 overflow-hidden ">
                    <div class="aspect-video relative radius-16 overflow-hidden">
                        <?php if ($post['thumbnail']): ?>
                        <img src="/<?= ltrim($post['thumbnail'], '/') ?>"
                            alt="<?= htmlspecialchars($post['title']) ?>"
                            class="w-full h-full object-cover transition-all">
                        <?php else: ?>
                        <div class="w-full h-full d-flex align-center justify-center ">
                            <i data-lucide="image" class="w-12 h-12 opacity-20"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-2 d-column gap-05">
                        <div class="d-flex align-center gap-05 mb-02">
                            <span class="text-primary font-bold"><?= htmlspecialchars($post['category_name'] ?? 'وبلاگ') ?></span>
                            <span class="text-subtitle opacity-30">•</span>
                            <span class="text-subtitle font-bold"><?= jalali_time_tag($post['created_at']) ?></span>
                        </div>
                        <h3 class="font-bold font-size-4 text-title line-clamp-2"><?= htmlspecialchars($post['title']) ?></h3>
                        <p class="line-clamp-2"><?= htmlspecialchars($post['excerpt']) ?></p>
                    </div>
                </a>
                <?php endforeach; ?>

                <?php if (empty($posts)): ?>
                <div class="basis-100 text-center p-2 bg-block border radius-20 d-column align-center gap-1 opacity-50">
                    <i data-lucide="newspaper" class="w-16 h-16 text-subtitle"></i>
                    <p class="text-subtitle font-bold font-size-2">مقاله‌ای یافت نشد.</p>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<style>
    .transition-all { transition: all 0.3s; }
    .aspect-video { aspect-ratio: 16 / 9; }
    .radius-20 { border-radius: 20px; }
    .radius-12 { border-radius: 12px; }

    .blog-card {
         color: var(--color-text)
    }
    .blog-card:hover img { transform: scale(1.05); }

    .glass-badge {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        padding: 4px 12px;
    }

    .btn-blog-outline {
        padding: 8px 20px;
        border-radius: 100px;
        border: 1.5px solid var(--color-primary);
        color: var(--color-primary);
        font-size: 11px;
        font-weight: 800;
        transition: all 0.3s;
    }
    .btn-blog-outline:hover {
        background: var(--color-primary);
        color: white;
    }

    .sticky-sidebar { position: sticky; top: 1rem; }

    .cat-link { color: var(--color-text); }
    .cat-link:hover { background: #f8fafc; color: var(--color-primary); }
    .cat-link.active { background: var(--bg-warning); color: var(--color-warning); }
    .cat-link.active .cat-arrow { transform: translateX(-5px); }
    .cat-arrow { transition: transform 0.3s; }

    .popular-item { text-decoration: none; }
    .popular-number {
        font-size: 24px;
        font-weight: 900;
        color: var(--color-primary);
        opacity: 0.15;
        line-height: 1;
        transition: opacity 0.3s;
    }
    .popular-item:hover .popular-number { opacity: 0.8; }
    .popular-item:hover h4 { color: var(--color-primary); }

    .bg-gradient-to-t {
        background-image: linear-gradient(to top, var(--tw-gradient-stops));
    }
    .from-black\/60 { --tw-gradient-from: rgb(0 0 0 / 0.6); --tw-gradient-stops: var(--tw-gradient-from), transparent; }

    @media (max-width: 768px) {
        .basis-300 { flex-basis: 100%; }
    }
</style>
