<div class="blog-container d-column gap-md">

    <?php if (!empty($featured_posts) && !isset($current_category)): ?>
    <section class="featured-posts mb-2">
        <div class="d-flex align-center gap-1 mb-1-5 pr-1">
            <div class="w-1 h-10 bg-primary radius-100"></div>
            <h2 class="font-size-4 font-black">مقالات برگزیده</h2>
        </div>
        <div class="d-flex-wrap gap-md">
            <?php foreach ($featured_posts as $post): ?>
            <a href="/blog/<?= htmlspecialchars($post['slug']) ?>" class="blog-card featured basis-300 grow-1 bg-block border radius-20 overflow-hidden d-column relative">
                <div class="aspect-video relative overflow-hidden bg-slate-100">
                    <?php if ($post['thumbnail']): ?>
                    <img src="/<?= ltrim($post['thumbnail'], '/') ?>"
                         alt="<?= htmlspecialchars($post['title']) ?>"
                         class="w-full h-full object-cover transition-all duration-700 hover:scale-105">
                    <?php else: ?>
                    <div class="w-full h-full d-flex align-center justify-center text-slate-300">
                        <i data-lucide="image" class="w-16 h-16 opacity-20"></i>
                    </div>
                    <?php endif; ?>
                    <div class="absolute bottom-0 right-0 left-0 p-2 bg-gradient-to-t from-black/60 to-transparent">
                        <span class="glass-badge text-white text-[10px] font-black px-3 py-07 radius-10">
                            <?= htmlspecialchars($post['category_name'] ?? 'وبلاگ') ?>
                        </span>
                    </div>
                </div>
                <div class="p-2 d-column gap-1">
                    <h3 class="font-black font-size-2 text-title line-clamp-2"><?= htmlspecialchars($post['title']) ?></h3>
                    <p class="text-subtitle text-[12px] line-clamp-2 leading-relaxed opacity-80"><?= htmlspecialchars($post['excerpt']) ?></p>
                    <div class="d-flex just-between align-center mt-05 pt-1 border-top">
                        <span class="text-[10px] text-subtitle d-flex align-center gap-05 font-bold">
                            <i data-lucide="calendar-days" class="icon-size-2 text-primary"></i>
                            <?= jalali_date($post['created_at']) ?>
                        </span>
                        <div class="text-primary text-[11px] font-black d-flex align-center gap-05">
                            مطالعه مقاله <i data-lucide="arrow-left" class="icon-size-2"></i>
                        </div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <div class="d-flex-wrap gap-md align-stretch">
        <div class="grow-8 basis-600 d-column gap-md">
            <section class="posts-list">
                <div class="d-flex just-between align-center mb-1-5 pr-1">
                    <div class="d-flex align-center gap-1">
                        <div class="w-1 h-8 bg-primary radius-100"></div>
                        <h2 class="font-size-3 font-black"><?= isset($current_category) ? 'مقالات ' . htmlspecialchars($current_category['name']) : 'آخرین نوشته‌ها' ?></h2>
                    </div>
                    <?php if (isset($current_category)): ?>
                    <a href="/blog" class="btn-blog-outline">مشاهده همه</a>
                    <?php endif; ?>
                </div>

                <div class="d-flex-wrap gap-md">
                    <?php foreach ($posts as $post): ?>
                    <a href="/blog/<?= htmlspecialchars($post['slug']) ?>" class="blog-card basis-300 grow-1 bg-block border radius-20 overflow-hidden d-column">
                        <div class="aspect-video relative overflow-hidden bg-slate-100">
                            <?php if ($post['thumbnail']): ?>
                            <img src="/<?= ltrim($post['thumbnail'], '/') ?>"
                                 alt="<?= htmlspecialchars($post['title']) ?>"
                                 class="w-full h-full object-cover transition-all duration-700">
                            <?php else: ?>
                            <div class="w-full h-full d-flex align-center justify-center text-slate-300">
                                <i data-lucide="image" class="w-12 h-12 opacity-20"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-1-5 d-column gap-05">
                            <div class="d-flex align-center gap-05 mb-02">
                                <span class="text-primary text-[10px] font-black"><?= htmlspecialchars($post['category_name'] ?? 'وبلاگ') ?></span>
                                <span class="text-subtitle opacity-30">•</span>
                                <span class="text-subtitle text-[10px] font-bold"><?= jalali_date($post['created_at']) ?></span>
                            </div>
                            <h3 class="font-bold font-size-1-2 text-title line-clamp-2 h-12"><?= htmlspecialchars($post['title']) ?></h3>
                            <p class="text-subtitle text-[11px] line-clamp-2 leading-relaxed opacity-80"><?= htmlspecialchars($post['excerpt']) ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>

                    <?php if (empty($posts)): ?>
                    <div class="basis-100 text-center py-10 bg-block border radius-20 d-column align-center gap-1 opacity-50">
                        <i data-lucide="newspaper" class="w-16 h-16 text-subtitle"></i>
                        <p class="text-subtitle font-black font-size-2">مقاله‌ای یافت نشد.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <aside class="grow-2 basis-250 d-column gap-md">
            <div class="blog-sidebar-card bg-block border radius-20 p-2 sticky-sidebar">
                <div class="d-flex align-center gap-05 mb-2 border-bottom pb-1">
                    <i data-lucide="layers-3" class="icon-size-4 text-primary"></i>
                    <h3 class="font-black font-size-1-2">دسته‌بندی‌ها</h3>
                </div>
                <ul class="d-column gap-02">
                    <?php foreach ($categories as $cat): ?>
                    <li>
                        <a href="/blog/category/<?= htmlspecialchars($cat['slug']) ?>"
                           class="cat-link d-flex just-between align-center p-1 radius-12 transition-all <?= (isset($current_category) && $current_category['id'] == $cat['id']) ? 'active' : '' ?>">
                            <span class="font-bold text-[13px]"><?= htmlspecialchars($cat['name']) ?></span>
                            <div class="cat-arrow"><i data-lucide="chevron-left" class="icon-size-2"></i></div>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="blog-sidebar-card bg-block border radius-20 p-2">
                <div class="d-flex align-center gap-05 mb-2 border-bottom pb-1">
                    <i data-lucide="flame" class="icon-size-4 text-error"></i>
                    <h3 class="font-black font-size-1-2">داغ‌ترین مطالب</h3>
                </div>
                <div class="d-column gap-1-5">
                    <?php
                    $popular = array_slice($posts, 0, 5);
                    foreach ($popular as $idx => $p):
                    ?>
                    <a href="/blog/<?= htmlspecialchars($p['slug']) ?>" class="popular-item d-flex gap-1 group">
                        <div class="popular-number"><?= $idx + 1 ?></div>
                        <div class="d-column gap-02">
                            <h4 class="text-[12px] font-black text-title line-clamp-2 transition-colors"><?= htmlspecialchars($p['title']) ?></h4>
                            <div class="d-flex align-center gap-05 opacity-50">
                                <i data-lucide="eye" class="icon-size-1"></i>
                                <span class="text-[9px]"><?= number_format($p['views']) ?> بازدید</span>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </div>
</div>

<style>
    .aspect-video { aspect-ratio: 16 / 9; }
    .radius-20 { border-radius: 20px; }
    .radius-12 { border-radius: 12px; }

    .blog-card {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .blog-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        border-color: var(--color-primary);
    }
    .blog-card:hover img { transform: scale(1.05); }

    .glass-badge {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.3);
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
