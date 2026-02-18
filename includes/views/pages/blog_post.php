<article class="blog-post-page d-flex-wrap gap-md align-stretch">
    <div class="grow-8 basis-600 d-column gap-md">
        <div class="post-main-card bg-block border pd-md radius-24 overflow-hidden ">

            <h1 class="font-black text-title mb-2"><?= htmlspecialchars($post['title']) ?></h1>

            <div class="d-flex-wrap align-center gap-1-5 mb-2">
                <?php if (!empty($all_categories)): ?>
                    <?php foreach ($all_categories as $cat): ?>
                        <a href="/blog/<?= htmlspecialchars($cat['slug']) ?>" class="category-badge">
                            <i data-lucide="hash" class="icon-size-2"></i>
                            <?= htmlspecialchars($cat['name']) ?>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <a href="/blog/<?= htmlspecialchars($post['category_slug'] ?? 'uncategorized') ?>" class="category-badge">
                        <i data-lucide="hash" class="icon-size-2"></i>
                        <?= htmlspecialchars($post['category_name'] ?? 'وبلاگ') ?>
                    </a>
                <?php endif; ?>
                <div class="meta-item">
                    <i data-lucide="calendar" class="icon-size-3"></i>
                    <span><?= jalali_time_tag($post['created_at'], 'weekday') ?></span>
                </div>
                <?php if (get_setting('blog_show_views', '1') == '1'): ?>
                <div class="meta-item">
                    <i data-lucide="eye" class="icon-size-3"></i>
                    <span><?= number_format($post['views']) ?> بازدید</span>
                </div>
                <?php endif; ?>
                <?php if (get_setting('blog_show_reading_time', '1') == '1'): ?>
                <div class="meta-item">
                    <i data-lucide="clock" class="icon-size-3"></i>
                    <?php
                    $text_only = strip_tags($post['content']);
                    // UTF-8 compatible word count for Persian
                    $word_count = count(preg_split('/\s+/u', $text_only, -1, PREG_SPLIT_NO_EMPTY));
                    $read_time = ceil($word_count / 200);
                    ?>
                    <span><?= $read_time ?> دقیقه مطالعه</span>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($post['excerpt']): ?>
                <div class="d-flex gap-1 mb-2">
                    <i data-lucide="quote" class="w-10 h-10 text-primary opacity-20 shrink-0"></i>
                    <p class="font-bold text-subtitle line-height-2"><?= htmlspecialchars($post['excerpt']) ?></p>
                </div>
            <?php endif; ?>

            <?php if ($post['thumbnail']): ?>
                <div class="post-hero-image radius-16 relative overflow-hidden mb-2">
                    <img src="/<?= ltrim($post['thumbnail'], '/') ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="w-full h-full object-cover">
                </div>
            <?php endif; ?>

            <div class="content-text font-size-2 line-height-2-5 text-title">
                <div id="toc-placeholder"></div>
                <?= $post['content'] ?>
            </div>

            <div class="post-footer mt-2 pt-2 border-top d-flex-wrap just-between align-center gapd-md">
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

        <?php if (!empty($faqs)): ?>
        <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "FAQPage",
          "mainEntity": [
            <?php foreach ($faqs as $i => $faq): ?>
            {
              "@type": "Question",
              "name": "<?= htmlspecialchars($faq['question']) ?>",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "<?= htmlspecialchars(strip_tags($faq['answer'])) ?>"
              }
            }<?= ($i < count($faqs) - 1) ? ',' : '' ?>
            <?php endforeach; ?>
          ]
        }
        </script>

        <section class="faq-section mt-4">
            <div class="bg-block pd-md border radius-24">
                <div class="d-flex align-center gap-1 pb-1 mb-2 border-bottom">
                    <div class="w-10 h-10 border radius-12 p-05 bg-secondary d-flex align-center just-center">
                        <i data-lucide="help-circle" color="var(--color-primary)" class="w-6 h-6"></i>
                    </div>
                    <h2 class="font-size-3 font-black">سوالات متداول این مقاله</h2>
                </div>

                <div class="faq-list d-column gap-1">
                    <?php foreach ($faqs as $faq): ?>
                        <div class="faq-item border radius-16 overflow-hidden transition-all hover:border-primary/30">
                            <div class="faq-question pd-md bg-secondary cursor-pointer d-flex just-between align-center" onclick="toggleFaq(this)">
                                <strong class="font-size-1 text-title"><?= htmlspecialchars($faq['question']) ?></strong>
                                <i data-lucide="chevron-down" class="w-4 h-4 transition-all opacity-40"></i>
                            </div>
                            <div class="faq-answer pd-md border-top d-none bg-white/50">
                                <p class="font-size-1-1 line-height-2 text-subtitle"><?= nl2br(htmlspecialchars($faq['answer'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <script>
            function toggleFaq(el) {
                const answer = el.nextElementSibling;
                const icon = el.querySelector('i');
                const isOpen = !answer.classList.contains('d-none');

                // Close all others in this list
                const parent = el.closest('.faq-list');
                parent.querySelectorAll('.faq-answer').forEach(a => a.classList.add('d-none'));
                parent.querySelectorAll('.faq-question i').forEach(i => i.style.transform = 'rotate(0deg)');

                if (!isOpen) {
                    answer.classList.remove('d-none');
                    icon.style.transform = 'rotate(180deg)';
                }
            }
        </script>
        <?php endif; ?>

        <?php if (!empty($related_posts)): ?>
        <section class="related-section mt-3">
            <div class="d-flex align-center gap-1 mb-2 pr-1">
                <div class="w-1 h-8 bg-primary radius-100"></div>
                <h2 class="font-size-3 font-black">مطالب مرتبط پیشنهادی</h2>
            </div>
            <div class="d-flex-wrap gap-md">
                <?php foreach ($related_posts as $rp): ?>
                <a href="/blog/<?= htmlspecialchars($rp['category_slug'] ?? 'uncategorized') ?>/<?= htmlspecialchars($rp['slug']) ?>" class="blog-card basis-250 grow-1 bg-block border radius-20 overflow-hidden d-column">
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
                        <h3 class="font-black text-[13px] ellipsis-y ellipsis-y line-clampd-md leading-snug text-title"><?= htmlspecialchars($rp['title']) ?></h3>
                        <div class="d-flex align-center gap-05 opacity-50">
                            <i data-lucide="calendar" class="icon-size-1"></i>
                            <span class="text-[9px]"><?= jalali_time_tag($rp['created_at']) ?></span>
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
    .post-main-card h1 { line-height: 1.5; }
    .aspect-video { aspect-ratio: 16 / 9; }
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

    .blog-card { transition: all 0.3s; }
    .blog-card:hover { transform: translateY(-5px); border-color: var(--color-primary); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }

</style>
