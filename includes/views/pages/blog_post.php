<article class="blog-post-page d-flex-wrap gap-md align-stretch">
    <div class="grow-8 basis-600 d-column gap-md">
        <div class="post-main-card bg-block border radius-24 overflow-hidden">
            <?php if ($post['thumbnail']): ?>
            <div class="post-hero-image relative overflow-hidden">
                <img src="/<?= ltrim($post['thumbnail'], '/') ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="w-full h-full object-cover">
                <div class="hero-overlay"></div>
            </div>
            <?php endif; ?>

            <div class="p-2 md:p-4">
                <div class="d-flex-wrap align-center gap-1-5 mb-2">
                    <a href="/blog/category/<?= htmlspecialchars($post['category_slug']) ?>" class="category-badge">
                        <i data-lucide="hash" class="icon-size-2"></i>
                        <?= htmlspecialchars($post['category_name'] ?? 'وبلاگ') ?>
                    </a>
                    <div class="meta-item">
                        <i data-lucide="calendar" class="icon-size-3"></i>
                        <span><?= jalali_date($post['created_at']) ?></span>
                    </div>
                    <div class="meta-item">
                        <i data-lucide="eye" class="icon-size-3"></i>
                        <span><?= number_format($post['views']) ?> بازدید</span>
                    </div>
                    <div class="meta-item">
                        <i data-lucide="clock" class="icon-size-3"></i>
                        <span><?= ceil(str_word_count(strip_tags($post['content'])) / 200) ?> دقیقه مطالعه</span>
                    </div>
                </div>

                <h1 class="post-title font-black text-title mb-2-5"><?= htmlspecialchars($post['title']) ?></h1>

                <?php if ($post['excerpt']): ?>
                <div class="post-excerpt bg-slate-50 radius-16 p-2 mb-3">
                    <div class="d-flex gap-1">
                        <i data-lucide="quote" class="w-10 h-10 text-primary opacity-20 shrink-0"></i>
                        <p class="font-bold text-subtitle line-height-2"><?= htmlspecialchars($post['excerpt']) ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <div class="content-text font-size-2 line-height-2-5 text-title">
                    <?= $post['content'] ?>
                </div>

                <div class="post-footer mt-5 pt-3 border-top d-flex-wrap just-between align-center gap-2">
                    <div class="d-flex align-center gap-1">
                        <span class="font-black text-subtitle text-[12px]">اشتراک‌گذاری:</span>
                        <div class="d-flex gap-05">
                            <a href="https://telegram.me/share/url?url=<?= urlencode(get_current_url()) ?>&text=<?= urlencode($post['title']) ?>" target="_blank" class="share-btn telegram">
                                <i data-lucide="send" class="icon-size-3"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?= urlencode(get_current_url()) ?>&text=<?= urlencode($post['title']) ?>" target="_blank" class="share-btn twitter">
                                <i data-lucide="twitter" class="icon-size-3"></i>
                            </a>
                            <a href="https://wa.me/?text=<?= urlencode($post['title'] . ' ' . get_current_url()) ?>" target="_blank" class="share-btn whatsapp">
                                <i data-lucide="message-circle" class="icon-size-3"></i>
                            </a>
                        </div>
                    </div>

                    <?php if ($post['tags']): ?>
                    <div class="d-flex-wrap gap-05">
                        <?php
                        $tags = explode(',', $post['tags']);
                        foreach ($tags as $tag):
                            if (!trim($tag)) continue;
                        ?>
                        <span class="tag-pill">#<?= htmlspecialchars(trim($tag)) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($related_posts)): ?>
        <section class="related-section mt-3">
            <div class="d-flex align-center gap-1 mb-2 pr-1">
                <div class="w-1 h-8 bg-primary radius-100"></div>
                <h2 class="font-size-3 font-black">مطالب مرتبط پیشنهادی</h2>
            </div>
            <div class="d-flex-wrap gap-md">
                <?php foreach ($related_posts as $rp): ?>
                <a href="/blog/<?= htmlspecialchars($rp['slug']) ?>" class="blog-card basis-250 grow-1 bg-block border radius-20 overflow-hidden d-column">
                    <div class="aspect-video relative overflow-hidden bg-slate-100">
                        <?php if ($rp['thumbnail']): ?>
                        <img src="/<?= ltrim($rp['thumbnail'], '/') ?>" alt="<?= htmlspecialchars($rp['title']) ?>" class="w-full h-full object-cover transition-all duration-500 hover:scale-110">
                        <?php else: ?>
                        <div class="w-full h-full d-flex align-center justify-center text-slate-200">
                            <i data-lucide="image" class="w-10 h-10"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-1-5 d-column gap-05">
                        <h3 class="font-black text-[13px] line-clamp-2 leading-snug text-title"><?= htmlspecialchars($rp['title']) ?></h3>
                        <div class="d-flex align-center gap-05 opacity-50">
                            <i data-lucide="calendar" class="icon-size-1"></i>
                            <span class="text-[9px]"><?= jalali_date($rp['created_at']) ?></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>

</article>

<style>
    .aspect-video { aspect-ratio: 16 / 9; }
    .radius-24 { border-radius: 24px; }
    .radius-16 { border-radius: 16px; }

    .post-hero-image { height: 400px; }
    @media (max-width: 768px) { .post-hero-image { height: 250px; } }

    .hero-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(to bottom, transparent 60%, rgba(0,0,0,0.4));
    }

    .category-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: var(--bg-warning);
        color: var(--color-warning);
        padding: 6px 16px;
        border-radius: 100px;
        font-size: 11px;
        font-weight: 800;
        transition: all 0.3s;
    }
    .category-badge:hover { filter: brightness(0.95); transform: translateY(-1px); }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        color: var(--color-text);
        opacity: 0.6;
        font-size: 11px;
        font-weight: 600;
    }

    .post-title {
        font-size: 2.8rem;
        line-height: 1.2;
        letter-spacing: -0.04em;
    }
    @media (max-width: 768px) { .post-title { font-size: 1.8rem; } }

    .share-btn {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
        border: 1px solid #eee;
        color: #888;
    }
    .share-btn:hover { transform: translateY(-3px); color: white; border-color: transparent; }
    .share-btn.telegram:hover { background: #0088cc; }
    .share-btn.twitter:hover { background: #1da1f2; }
    .share-btn.whatsapp:hover { background: #25d366; }

    .tag-pill {
        background: #f8fafc;
        color: #64748b;
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        border: 1px solid #edf2f7;
        transition: all 0.2s;
    }
    .tag-pill:hover { background: #edf2f7; color: var(--color-primary); }

    .sticky-sidebar { position: sticky; top: 1.5rem; }

    .newsletter-card {
        background: linear-gradient(135deg, var(--color-primary), #8b5702);
        box-shadow: 0 15px 30px -10px rgba(194, 120, 3, 0.4);
    }
    .newsletter-glow {
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: rotate 20s linear infinite;
    }
    @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

    .newsletter-input {
        width: 100%;
        background: rgba(255,255,255,0.15);
        border: 1px solid rgba(255,255,255,0.2);
        padding: 12px 16px;
        border-radius: 14px;
        color: white;
        font-size: 13px;
        outline: none;
        transition: all 0.3s;
    }
    .newsletter-input::placeholder { color: rgba(255,255,255,0.6); }
    .newsletter-input:focus { background: rgba(255,255,255,0.25); border-color: white; }

    .newsletter-btn {
        width: 100%;
        background: white;
        color: var(--color-primary);
        padding: 12px;
        border-radius: 14px;
        font-weight: 900;
        font-size: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.3s;
    }
    .newsletter-btn:hover { transform: scale(1.02); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }

    .blog-card { transition: all 0.3s; }
    .blog-card:hover { transform: translateY(-5px); border-color: var(--color-primary); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }

    .toc-item {
        display: block;
        padding: 8px 12px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        color: var(--color-text);
        transition: all 0.2s;
    }
    .toc-item:hover { background: #f8fafc; color: var(--color-primary); }
    .toc-item.active { background: rgba(194, 120, 3, 0.05); color: var(--color-primary); border-right: 3px solid var(--color-primary); }
</style>
