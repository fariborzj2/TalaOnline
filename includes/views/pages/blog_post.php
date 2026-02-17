<article class="blog-post-container d-flex-wrap gap-md align-stretch">
    <div class="grow-8 basis-600 d-column gap-md">
        <div class="bg-block border radius-20 overflow-hidden">
            <?php if ($post['thumbnail']): ?>
            <div class="post-header-image aspect-[21/9] w-full overflow-hidden">
                <img src="/<?= ltrim($post['thumbnail'], '/') ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="w-full h-full object-cover">
            </div>
            <?php endif; ?>

            <div class="p-2 md:p-3">
                <div class="d-flex align-center gap-1 mb-1-5">
                    <a href="/blog/category/<?= htmlspecialchars($post['category_slug']) ?>" class="bg-primary-light text-primary text-[11px] font-black px-3 py-07 radius-10 border border-primary/10">
                        <?= htmlspecialchars($post['category_name'] ?? 'وبلاگ') ?>
                    </a>
                    <div class="d-flex align-center gap-05 text-subtitle text-[11px] font-bold">
                        <i data-lucide="calendar" class="icon-size-3"></i>
                        <?= jalali_date($post['created_at']) ?>
                    </div>
                    <div class="d-flex align-center gap-05 text-subtitle text-[11px] font-bold">
                        <i data-lucide="eye" class="icon-size-3"></i>
                        <?= number_format($post['views']) ?> بازدید
                    </div>
                </div>

                <h1 class="font-size-3 font-black text-title mb-2 leading-tight"><?= htmlspecialchars($post['title']) ?></h1>

                <?php if ($post['excerpt']): ?>
                <div class="bg-slate-50 border-r-4 border-primary p-1-5 radius-10 mb-2-5">
                    <p class="text-subtitle font-bold leading-relaxed"><?= htmlspecialchars($post['excerpt']) ?></p>
                </div>
                <?php endif; ?>

                <div class="content-text font-size-1-1 leading-loose text-title">
                    <?= $post['content'] ?>
                </div>

                <div class="mt-4 pt-3 border-t d-flex just-between align-center">
                    <div class="d-flex align-center gap-1">
                        <span class="text-subtitle text-[11px] font-bold">اشتراک‌گذاری:</span>
                        <div class="d-flex gap-05">
                            <a href="https://telegram.me/share/url?url=<?= urlencode(get_current_url()) ?>&text=<?= urlencode($post['title']) ?>" target="_blank" class="w-8 h-8 radius-10 border d-flex align-center justify-center text-subtitle hover:bg-blue-500 hover:text-white transition-all">
                                <i data-lucide="send" class="icon-size-3"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?= urlencode(get_current_url()) ?>&text=<?= urlencode($post['title']) ?>" target="_blank" class="w-8 h-8 radius-10 border d-flex align-center justify-center text-subtitle hover:bg-sky-400 hover:text-white transition-all">
                                <i data-lucide="twitter" class="icon-size-3"></i>
                            </a>
                        </div>
                    </div>
                    <div class="d-flex gap-05">
                        <?php if ($post['meta_keywords']): ?>
                            <?php
                            $tags = explode(',', $post['meta_keywords']);
                            foreach ($tags as $tag):
                                if (!trim($tag)) continue;
                            ?>
                            <span class="text-[10px] font-bold text-subtitle bg-slate-50 px-2 py-05 radius-5 border">#<?= htmlspecialchars(trim($tag)) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($related_posts)): ?>
        <section class="related-posts mt-2">
            <h2 class="font-size-2 font-black mb-1-5">مطالب مرتبط</h2>
            <div class="grid-3 gap-md">
                <?php foreach ($related_posts as $rp): ?>
                <a href="/blog/<?= htmlspecialchars($rp['slug']) ?>" class="blog-card bg-block border radius-15 overflow-hidden d-column">
                    <div class="aspect-video relative overflow-hidden bg-slate-100">
                        <?php if ($rp['thumbnail']): ?>
                        <img src="/<?= ltrim($rp['thumbnail'], '/') ?>" alt="<?= htmlspecialchars($rp['title']) ?>" class="w-full h-full object-cover transition-transform duration-500 hover:scale-110">
                        <?php else: ?>
                        <div class="w-full h-full d-flex align-center justify-center text-slate-300">
                            <i data-lucide="image" class="icon-size-5"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-1 d-column gap-05">
                        <h3 class="font-bold text-[12px] line-clamp-2 leading-snug"><?= htmlspecialchars($rp['title']) ?></h3>
                        <span class="text-[9px] text-subtitle"><?= jalali_date($rp['created_at']) ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>

    <aside class="grow-2 basis-250 d-column gap-md">
        <div class="bg-block border radius-15 p-1-5 sticky-sidebar">
            <div class="toc-container" id="toc-container">
                <h3 class="font-black font-size-1-1 mb-1-5 border-b pb-1 d-flex align-center gap-05">
                    <i data-lucide="list-ordered" class="icon-size-3 text-primary"></i>
                    فهرست مطالب
                </h3>
                <!-- TOC will be injected by app.js -->
            </div>
        </div>

        <div class="bg-primary rounded-20 p-2 text-white d-column gap-1-5 relative overflow-hidden">
            <i data-lucide="sparkles" class="absolute -right-2 -top-2 icon-size-20 opacity-10"></i>
            <h3 class="font-black font-size-1-2 relative z-10">عضویت در خبرنامه</h3>
            <p class="text-[11px] leading-relaxed opacity-90 relative z-10">آخرین تحلیل‌های بازار طلا و ارز را مستقیماً در ایمیل خود دریافت کنید.</p>
            <div class="relative z-10 d-column gap-05">
                <input type="email" placeholder="ایمیل شما..." class="w-full bg-white/10 border border-white/20 radius-10 p-1 text-[11px] text-white placeholder:text-white/50 outline-none focus:bg-white/20 transition-all">
                <button class="w-full bg-white text-primary font-black py-1 radius-10 text-[11px] hover:bg-opacity-90 transition-all">عضویت رایگان</button>
            </div>
        </div>
    </aside>
</article>

<style>
    .post-header-image { position: relative; }
    .content-text { color: #2d3748; }
    .content-text h2 { font-size: 1.5rem; font-weight: 800; margin-top: 2rem; margin-bottom: 1rem; color: #1a202c; }
    .content-text h3 { font-size: 1.25rem; font-weight: 800; margin-top: 1.5rem; margin-bottom: 0.75rem; color: #1a202c; }
    .content-text p { margin-bottom: 1.5rem; }
    .content-text ul, .content-text ol { margin-bottom: 1.5rem; padding-right: 1.5rem; }
    .content-text li { margin-bottom: 0.5rem; }
    .content-text img { border-radius: 1rem; margin: 2rem 0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }

    .toc-link { display: block; padding: 0.5rem 0.75rem; border-radius: 0.5rem; font-size: 0.8rem; font-weight: 600; color: var(--text-subtitle); transition: all 0.2s; }
    .toc-link:hover { color: var(--primary); background: rgba(79, 70, 229, 0.05); }
    .toc-link.active { color: var(--primary); background: rgba(79, 70, 229, 0.1); }

    .aspect-video { aspect-ratio: 16 / 9; }
</style>
