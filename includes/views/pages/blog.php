<div class="blog-container d-column gap-md">

    <?php if (!empty($featured_posts) && !isset($current_category)): ?>
    <section class="featured-posts">
        <h2 class="font-size-2 mb-1">مقالات برگزیده</h2>
        <div class="grid-3 gap-md">
            <?php foreach ($featured_posts as $post): ?>
            <a href="/blog/<?= htmlspecialchars($post['slug']) ?>" class="blog-card featured bg-block border radius-15 overflow-hidden d-column">
                <div class="aspect-video relative overflow-hidden bg-slate-100">
                    <?php if ($post['thumbnail']): ?>
                    <img src="/<?= ltrim($post['thumbnail'], '/') ?>"
                         alt="<?= htmlspecialchars($post['title']) ?>"
                         class="w-full h-full object-cover transition-transform duration-500 hover:scale-110">
                    <?php else: ?>
                    <div class="w-full h-full d-flex align-center justify-center text-slate-300">
                        <i data-lucide="image" class="icon-size-10"></i>
                    </div>
                    <?php endif; ?>
                    <div class="absolute top-1 right-1">
                        <span class="bg-primary text-white text-[10px] font-bold px-2 py-05 radius-5">
                            <?= htmlspecialchars($post['category_name'] ?? 'وبلاگ') ?>
                        </span>
                    </div>
                </div>
                <div class="p-1-5 d-column gap-05">
                    <h3 class="font-bold font-size-1-2 line-clamp-1"><?= htmlspecialchars($post['title']) ?></h3>
                    <p class="text-subtitle text-[11px] line-clamp-2 leading-relaxed"><?= htmlspecialchars($post['excerpt']) ?></p>
                    <div class="d-flex just-between align-center mt-1 pt-1 border-t">
                        <span class="text-[10px] text-subtitle d-flex align-center gap-05">
                            <i data-lucide="calendar" class="icon-size-2"></i>
                            <?= jalali_date($post['created_at']) ?>
                        </span>
                        <span class="text-primary text-[10px] font-bold d-flex align-center gap-02">
                            ادامه مطلب <i data-lucide="chevron-left" class="icon-size-2"></i>
                        </span>
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
                <div class="d-flex just-between align-center mb-1">
                    <h2 class="font-size-2"><?= isset($current_category) ? 'مقالات ' . htmlspecialchars($current_category['name']) : 'آخرین مقالات' ?></h2>
                    <?php if (isset($current_category)): ?>
                    <a href="/blog" class="text-primary text-[11px] font-bold">مشاهده همه</a>
                    <?php endif; ?>
                </div>

                <div class="grid-2 gap-md">
                    <?php foreach ($posts as $post): ?>
                    <a href="/blog/<?= htmlspecialchars($post['slug']) ?>" class="blog-card bg-block border radius-15 overflow-hidden d-column">
                        <div class="aspect-video relative overflow-hidden bg-slate-100">
                            <?php if ($post['thumbnail']): ?>
                            <img src="/<?= ltrim($post['thumbnail'], '/') ?>"
                                 alt="<?= htmlspecialchars($post['title']) ?>"
                                 class="w-full h-full object-cover transition-transform duration-500 hover:scale-110">
                            <?php else: ?>
                            <div class="w-full h-full d-flex align-center justify-center text-slate-300">
                                <i data-lucide="image" class="icon-size-8"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-1-5 d-column gap-05">
                            <div class="d-flex align-center gap-05 mb-02">
                                <span class="text-primary text-[9px] font-black uppercase"><?= htmlspecialchars($post['category_name'] ?? 'وبلاگ') ?></span>
                                <span class="text-subtitle text-[18px] opacity-20">•</span>
                                <span class="text-subtitle text-[9px]"><?= jalali_date($post['created_at']) ?></span>
                            </div>
                            <h3 class="font-bold font-size-1 line-clamp-2 h-10"><?= htmlspecialchars($post['title']) ?></h3>
                            <p class="text-subtitle text-[11px] line-clamp-2 leading-relaxed"><?= htmlspecialchars($post['excerpt']) ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>

                    <?php if (empty($posts)): ?>
                    <div class="col-span-2 text-center py-10 bg-block border radius-15 d-column align-center gap-1">
                        <i data-lucide="newspaper" class="icon-size-10 text-subtitle opacity-20"></i>
                        <p class="text-subtitle font-bold">مقاله‌ای در این بخش یافت نشد.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <aside class="grow-2 basis-250 d-column gap-md">
            <div class="bg-block border radius-15 p-1-5 sticky-sidebar">
                <h3 class="font-black font-size-1-1 mb-1-5 border-b pb-1 d-flex align-center gap-05">
                    <i data-lucide="layers" class="icon-size-3 text-primary"></i>
                    دسته‌بندی‌ها
                </h3>
                <ul class="d-column gap-05">
                    <?php foreach ($categories as $cat): ?>
                    <li>
                        <a href="/blog/category/<?= htmlspecialchars($cat['slug']) ?>"
                           class="d-flex just-between align-center p-07 radius-10 transition-all hover:bg-slate-50 <?= (isset($current_category) && $current_category['id'] == $cat['id']) ? 'bg-primary-light text-primary font-black' : 'text-title font-bold' ?>">
                            <span><?= htmlspecialchars($cat['name']) ?></span>
                            <i data-lucide="chevron-left" class="icon-size-2"></i>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="bg-block border radius-15 p-1-5">
                <h3 class="font-black font-size-1-1 mb-1-5 border-b pb-1 d-flex align-center gap-05">
                    <i data-lucide="trending-up" class="icon-size-3 text-primary"></i>
                    پرطرفدارترین‌ها
                </h3>
                <div class="d-column gap-1">
                    <?php
                    // Showing a few posts as popular (we could fetch by views)
                    $popular = array_slice($posts, 0, 5);
                    foreach ($popular as $idx => $p):
                    ?>
                    <a href="/blog/<?= htmlspecialchars($p['slug']) ?>" class="d-flex gap-1 group">
                        <span class="font-black text-2xl text-subtitle opacity-20 group-hover:text-primary group-hover:opacity-100 transition-all"><?= $idx + 1 ?></span>
                        <div class="d-column gap-02">
                            <h4 class="text-[11px] font-black leading-snug line-clamp-2 group-hover:text-primary transition-colors"><?= htmlspecialchars($p['title']) ?></h4>
                            <span class="text-[9px] text-subtitle"><?= jalali_date($p['created_at']) ?></span>
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
    .bg-primary-light { background-color: rgba(79, 70, 229, 0.05); }
    .sticky-sidebar { position: sticky; top: 1rem; }
    .blog-card { transition: all 0.3s ease; }
    .blog-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px -10px rgba(0,0,0,0.1); border-color: var(--primary); }
</style>
