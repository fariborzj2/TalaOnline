<?php
/**
 * Comments Component (Server-Side Rendering)
 * @var array $comments
 * @var string $target_id
 * @var string $target_type
 * @var int $total_pages
 * @var int $current_page
 * @var bool $read_only
 */

$is_logged_in = isset($_SESSION['user_id']);
$read_only = $read_only ?? in_array($target_type, ['user_profile', 'explore']);
$guest_view_enabled = get_setting('comments_guest_view', '1') === '1';
$guest_comment_enabled = get_setting('comments_guest_comment_' . $target_type, '0') === '1';

function render_comment_item($c, $read_only = false, $is_reply = false, $target_type = '') {
    global $pdo;
    $has_replies = !$is_reply && (!empty($c['replies']) || ($c['total_replies'] ?? 0) > 0);
    $is_expert = in_array($c['user_role'], ['admin', 'editor']);
    $default_avatar = '/assets/images/default-avatar.png';
    $avatar = $c['user_avatar'] ?: $default_avatar;
    if ($avatar && !str_starts_with($avatar, 'http') && !str_starts_with($avatar, '/')) {
        $avatar = '/' . $avatar;
    }

    ob_start();
    ?>
    <div class="comment-wrapper <?= $has_replies ? 'has-replies' : '' ?>" id="comment-wrapper-<?= $c['id'] ?>">
        <div class="comment-item <?= $is_expert ? 'is-expert' : '' ?> <?= $is_reply ? 'is-reply' : '' ?>" id="comment-<?= $c['id'] ?>">
            <div class="comment-header">
                <div class="comment-user-info">
                    <div class="avatar-container">
                        <img src="<?= htmlspecialchars($avatar) ?>" class="comment-avatar" alt="<?= htmlspecialchars($c['user_name'] ?: ($c['guest_name'] ?: 'ناشناس')) ?>" onerror="this.src='<?= $default_avatar ?>'">
                        <div class="online-dot"></div>
                    </div>
                    <div class="comment-meta">
                        <?php if ($c['user_id']): ?>
                            <a href="/profile/<?= $c['user_id'] ?>/<?= urlencode($c['user_username'] ?? 'user') ?>" class="comment-author">
                                <span class="d-inline-block ellipsis-x max-w100"><?= htmlspecialchars($c['user_name']) ?></span>
                                <span class="user-level-badge level-<?= $c['user_level'] ?>">سطح <?= $c['user_level'] ?></span>
                            </a>
                        <?php else: ?>
                            <span class="comment-author">
                                <span class="d-inline-block ellipsis-x max-w100"><?= htmlspecialchars($c['guest_name'] ?: 'مهمان') ?></span>
                                <span class="user-level-badge !bg-slate-400">مهمان</span>
                            </span>
                        <?php endif; ?>

                        <span class="comment-date"><?= jalali_date($c['created_at']) ?></span>
                    </div>
                </div>
                <div class="header-actions">
                    <?php if ($c['can_edit']): ?>
                        <div class="comment-header-btn delete-btn" title="حذف" data-id="<?= $c['id'] ?>"><i data-lucide="trash-2" class="icon-size-4"></i></div>
                        <div class="comment-header-btn edit-btn" title="ویرایش" data-id="<?= $c['id'] ?>"><i data-lucide="edit-3" class="icon-size-4"></i></div>
                    <?php endif; ?>
                    <div class="comment-header-btn report-btn" title="گزارش تخلف" data-id="<?= $c['id'] ?>"><i data-lucide="flag" class="icon-size-4"></i></div>
                    <div class="comment-header-btn comment-share-btn" title="کپی لینک مستقیم" data-id="<?= $c['id'] ?>" data-target-url="<?= $c['target_info']['url'] ?? '' ?>">
                        <i data-lucide="share-2" class="icon-size-3"></i>
                    </div>
                </div>
            </div>

            <?php if (isset($c['target_info']) && $c['target_info']): ?>
                <div class="d-inline-flex font-bold font-size-2 mb-1">
                    <span class="text-gray-400">در </span>
                    <a href="<?= $c['target_info']['url'] ?>" class="text-primary hover-underline"><?= htmlspecialchars($c['target_info']['title']) ?></a>
                </div>
            <?php endif; ?>

            <div class="comment-content">
                <?php if (!empty($c['reply_to_content'])): ?>
                    <?php
                        $plain_reply_content = html_entity_decode(strip_tags($c['reply_to_content']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $plain_reply_content = trim(preg_replace('/\s+/', ' ', $plain_reply_content));
                        $preview_reply_content = mb_substr($plain_reply_content, 0, 100);
                        $reply_dots = mb_strlen($plain_reply_content) > 100 ? '...' : '';
                    ?>
                    <div class="reply-preview-block">
                        <div>در پاسخ به <a href="/profile/<?= $c['reply_to_user_id'] ?>/<?= urlencode($c['reply_to_username'] ?? 'user') ?>" class="reply-preview-author">@<?= htmlspecialchars($c['reply_to_username'] ?? 'user') ?></a></div>
                        <div class="reply-preview-content"><?= htmlspecialchars($preview_reply_content) . $reply_dots ?></div>
                    </div>
                <?php endif; ?>
                <div class="comment-body-text"><?= $c['content_html'] ?></div>
                <?php if ($c['type'] === 'analysis' && $c['image_url']): ?>
                    <div class="comment-attachment mb-2">
                        <a href="/<?= htmlspecialchars($c['image_url']) ?>" target="_blank" class="radius-12 overflow-hidden transition-all">
                            <img src="/<?= htmlspecialchars($c['image_url']) ?>" alt="تحلیل کاربر" class="w-full object-contain bg-secondary">
                        </a>
                    </div>
                <?php endif; ?>
                <?php if ($is_expert): ?>
                    <div class="attachment-btn"><i data-lucide="file-text" class="icon-size-4"></i> مشاهده پیوست</div>
                <?php endif; ?>
            </div>

            <div class="comment-footer">
                <?php if (!$read_only): ?>
                    <div class="comment-footer-btn reply-btn" data-id="<?= $c['id'] ?>">
                        <i data-lucide="reply" class="icon-size-4"></i>
                        <span>پاسخ</span>
                    </div>
                <?php elseif (in_array($target_type, ['user_profile', 'explore']) && !$is_reply): ?>
                    <div class="view-thread-btn comment-footer-btn" data-id="<?= $c['id'] ?>">
                        <i data-lucide="message-circle" class="icon-size-3"></i>
                        <span><?= ($c['total_replies'] ?? 0) > 0 ? fa_num($c['total_replies']) . ' پاسخ' : 'بدون پاسخ' ?></span>
                    </div>
                <?php endif; ?>

                <div class="footer-right mr-auto">
                    <?php
                    $reactions_html = render_reaction($c, 'like', '👍') .
                                     render_reaction($c, 'heart', '❤️') .
                                     render_reaction($c, 'fire', '🔥') .
                                     render_reaction($c, 'dislike', '👎');
                    ?>
                    <div class="reaction-pill <?= empty($reactions_html) ? 'd-none' : '' ?>">
                        <?= $reactions_html ?>
                    </div>
                    <div class="comment-footer-btn btn-react-trigger" data-id="<?= $c['id'] ?>">
                        <i data-lucide="smile" class="icon-size-4"></i>
                        <span>واکنش</span>
                    </div>
                    <div class="reactions-popover" id="popover-<?= $c['id'] ?>">
                        <span class="emoji-btn" data-id="<?= $c['id'] ?>" data-type="like">👍</span>
                        <span class="emoji-btn" data-id="<?= $c['id'] ?>" data-type="heart">❤️</span>
                        <span class="emoji-btn" data-id="<?= $c['id'] ?>" data-type="fire">🔥</span>
                        <span class="emoji-btn" data-id="<?= $c['id'] ?>" data-type="dislike">👎</span>
                    </div>
                </div>
            </div>
        </div>

        <div id="reply-form-container-<?= $c['id'] ?>"></div>

        <?php if ($has_replies && !in_array($target_type, ['user_profile', 'explore'])): ?>
            <div class="replies-container" id="replies-container-<?= $c['id'] ?>">
                <div class="replies-list">
                    <?php if (!empty($c['replies'])) foreach ($c['replies'] as $reply) echo render_comment_item($reply, $read_only, true, $target_type); ?>
                </div>
                <?php if (($c['total_replies'] ?? 0) > 3): ?>
                    <button class="btn btn-sm btn-secondary w-full mt-2 view-more-replies"
                            data-id="<?= $c['id'] ?>"
                            data-total="<?= $c['total_replies'] ?>">
                        مشاهده پاسخ‌های بیشتر (<?= fa_num($c['total_replies'] - 3) ?>)
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

function render_reaction($c, $type, $emoji) {
    $count = $c[$type . 's'] ?? 0;
    if ($count == 0 && ($c['user_reaction'] ?? '') !== $type) return '';
    $active = ($c['user_reaction'] ?? '') === $type ? 'active' : '';
    return "<div class='reaction-pill-item $active' data-id='{$c['id']}' data-type='$type'><span>" . fa_num($count) . "</span> $emoji</div>";
}
?>

<div class="comments-section d-column gap-md <?= $read_only ? 'read-only' : '' ?> "
     id="comments-app"
     data-target-id="<?= $target_id ?>"
     data-target-type="<?= $target_type ?>"
     data-guest-comment="<?= $guest_comment_enabled ? '1' : '0' ?>"
     data-per-page="<?= (int)(in_array($target_type, ['user_profile', 'explore']) ? 10 : get_setting('comments_per_page', '20')) ?>"
     data-total-pages="<?= (int)($total_pages ?? 1) ?>"
     data-total-count="<?= (int)($total_count ?? 0) ?>"
     data-current-page="<?= (int)($current_page ?? 1) ?>">
    <?php if (!$guest_view_enabled && !$is_logged_in): ?>
        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-8 text-center">
            <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center text-slate-400 border border-slate-100 mx-auto mb-4">
                <i data-lucide="lock" class="w-8 h-8"></i>
            </div>
            <h3 class="text-slate-800 font-black mb-2">مشاهده نظرات محدود شده است</h3>
            <p class="text-slate-500 font-bold text-sm mb-6">برای مشاهده نظرات کاربران و شرکت در گفتگوها، ابتدا باید وارد حساب کاربری خود شوید.</p>
            <div class="flex items-center justify-center gap-3">
                <button class="btn btn-primary radius-10 px-6" onclick="window.showAuthModal?.('login')">ورود به حساب</button>
                <button class="btn btn-secondary radius-10 px-6" onclick="window.showAuthModal?.('register')">ثبت‌نام</button>
            </div>
        </div>
    <?php return; endif; ?>

    <?php if (!$read_only): ?>
        <div class="comments-header">
            <i data-lucide="message-square" class="text-primary w-6 h-6"></i>
            <h3>نظرات کاربران <span class="comments-count-badge">(<?= fa_num($total_count ?? 0) ?>)</span></h3>
        </div>

        <div id="main-form-container">
            <?php if ($is_logged_in || $guest_comment_enabled): ?>
                <div class="comment-form" id="form-main">
                    <div class="comment-type-selector d-flex gap-1-5 mb-1 pr-1 <?= $target_type === 'post' ? 'd-none' : '' ?>">
                        <label class="d-flex align-center gap-05 cursor-pointer font-bold text-sm">
                            <input type="radio" name="comment_type_main" value="comment" class="comment-type-radio" data-suffix="main" checked>
                            <span>نظر</span>
                        </label>
                        <label class="d-flex align-center gap-05 cursor-pointer font-bold text-sm">
                            <input type="radio" name="comment_type_main" value="analysis" class="comment-type-radio" data-suffix="main">
                            <span>تحلیل</span>
                        </label>
                    </div>

                    <?php if (!$is_logged_in && $guest_comment_enabled): ?>
                        <div class="d-flex-wrap gap-1 mb-1">
                            <div class="input-item grow-1">
                                <i data-lucide="user" class="text-gray icon-size-3"></i>
                                <input type="text" id="guest-name-main" placeholder="نام شما">
                            </div>
                            <div class="input-item grow-1">
                                <i data-lucide="mail" class="text-gray icon-size-3"></i>
                                <input type="email" id="guest-email-main" placeholder="ایمیل شما" dir="ltr" class="text-left">
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-1">
                        <textarea placeholder="دیدگاه تخصصی خود را اینجا بنویسید..." id="textarea-main"></textarea>
                    </div>

                    <?php if ($is_logged_in): ?>
                    <div class="mention-tag-area mb-2" id="mention-area-main">
                        <div class="mention-input-wrapper relative d-flex-wrap gap-05 align-center" id="mentions-container-main">
                            <i data-lucide="user-search" class="text-gray icon-size-5"></i>
                            <input type="text"
                                   class="mention-tag-input font-size-0-8"
                                   placeholder="منشن کردن کاربر..."
                                   id="mention-input-main">
                            <div class="mention-suggestions d-none" id="suggestions-main"></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="comment-image-upload d-none mb-2" id="image-upload-container-main">
                        <label for="comment-image-main" class="upload-zone d-flex align-center just-center gap-1 p-1 radius-8 border-dashed pointer transition-all">
                            <i data-lucide="image" class="text-gray icon-size-5"></i>
                            <div class="text-right">
                                <div class="font-bold font-size-2 text-title">آپلود تصویر تحلیل</div>
                            </div>
                            <input type="file" id="comment-image-main" class="d-none comment-image-input" accept="image/*" data-suffix="main">
                        </label>
                        <div class="font-size-1 text-gray">فرمت‌های مجاز: PNG, JPG, WebP, AVIF</div>
                        <div class="image-preview d-none mt-2 relative radius-12 overflow-hidden border" style="width: 100px; height: 100px;">
                            <img src="" class="w-full h-full object-cover">
                            <button type="button" class="remove-preview absolute top-0 left-0 m-05 radius-50 p-05" data-suffix="main">
                                <i data-lucide="x" class="icon-size-1"></i>
                            </button>
                        </div>
                    </div>

                    <div class="comment-form-footer">
                        <div class="comment-form-footer-left mr-auto d-flex align-center gap-1">
                            <button class="btn btn-primary submit-comment radius-10" data-parent="" data-edit="false">ارسال نظر</button>
                        </div>
                    </div>
                </div>
            <?php elseif (!$is_logged_in): ?>
                <div class="bg-orange-light pd-md radius-16 border border-orange d-flex-wrap just-between align-center gap-1">
                    <p class="font-bold text-orange">برای ثبت نظر و کسب امتیاز باید وارد حساب خود شوید</p>
                    <div class="d-flex gap-1">
                        <button class="btn btn-orange btn-sm" onclick="window.showAuthModal?.('login')">ورود به حساب</button>
                        <button class="btn btn-secondary btn-sm bg-block" onclick="window.showAuthModal?.('register')">عضویت رایگان</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="bg-block border radius-16 pd-md gap-1" id="comments-controls">
        <?php if ($target_type !== 'post'): ?>
            <div class="filter-group-container">
                <div class="pill-toggle-group filter-toggle-group">
                    <?php if ($target_type === 'explore'): ?>
                        <button class="pill-btn filter-btn" data-filter="all">همه</button>
                        <button class="pill-btn filter-btn" data-filter="comment">نظرات</button>
                        <button class="pill-btn filter-btn active" data-filter="analysis">تحلیل‌ها</button>
                    <?php else: ?>
                        <button class="pill-btn filter-btn active" data-filter="all">همه</button>
                        <button class="pill-btn filter-btn" data-filter="comment">نظرات</button>
                        <button class="pill-btn filter-btn" data-filter="analysis">تحلیل‌ها</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="sort-group d-flex align-center just-between gap-1">
            <div class="d-flex align-center gap-05 text-title">
                <i data-lucide="arrow-down-wide-narrow" class="icon-size-4"></i>
                <span class="font-bold sm-hide">مرتب‌سازی:</span>
            </div>
            <div class="d-flex align-center gap-1-5">
                <span class="sort-item sort-btn active" data-sort="newest">جدیدترین</span>
                <span class="sort-item sort-btn" data-sort="popular">محبوب‌ترین</span>
                <span class="sort-item sort-btn" data-sort="most_replies">بیشترین پاسخ</span>
            </div>
        </div>
    </div>

    <div class="comment-list <?= $read_only ? 'mt-0' : 'mt-0' ?>" id="comment-list">
        <?php if (empty($comments)): ?>
            <div class="bg-block text-center pd-md radius-16 border d-column just-center align-center">
                <i data-lucide="message-circle" class="w-12 h-12 text-gray-300 mx-auto mb-1"></i>
                <p class="text-gray-400"><?= $read_only ? 'هنوز نظری ثبت نشده است.' : 'هنوز نظری ثبت نشده است. اولین تحلیل‌گر باشید!' ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($comments as $c) echo render_comment_item($c, $read_only, !!($c['parent_id'] ?? false), $target_type); ?>
        <?php endif; ?>
    </div>

    <?php if (($total_pages ?? 1) > 1): ?>
        <div class="pagination <?= (in_array($target_type, ['user_profile', 'explore'])) ? 'd-none' : '' ?>" id="comments-pagination">
            <?php
            $pagination_items = get_pagination_window($current_page, $total_pages);

            if ($current_page > 1): ?>
                <button class="pagination-link page-btn" data-page="<?= $current_page - 1 ?>" title="صفحه قبل">
                    <i data-lucide="chevron-right" class="icon-size-4"></i>
                </button>
            <?php endif; ?>

            <?php foreach ($pagination_items as $item): ?>
                <?php if ($item === '...'): ?>
                    <span class="pagination-dots">...</span>
                <?php else: ?>
                    <button class="pagination-link page-btn <?= $item == ($current_page ?? 1) ? 'active' : '' ?>" data-page="<?= $item ?>">
                        <?= fa_num($item) ?>
                    </button>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($current_page < $total_pages): ?>
                <button class="pagination-link page-btn" data-page="<?= $current_page + 1 ?>" title="صفحه بعد">
                    <i data-lucide="chevron-left" class="icon-size-4"></i>
                </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <script>
        window.__COMMENTS_INITIAL_DATA__ = window.__COMMENTS_INITIAL_DATA__ || {};
        window.__COMMENTS_INITIAL_DATA__['<?= $target_type . '_' . $target_id ?>'] = {
            comments: <?= json_encode($comments, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            total_count: <?= (int)($total_count ?? 0) ?>,
            total_pages: <?= (int)($total_pages ?? 1) ?>,
            current_page: <?= (int)($current_page ?? 1) ?>
        };
    </script>
</div>
