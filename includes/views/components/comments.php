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
$read_only = $read_only ?? ($target_type === 'user_profile');

function render_comment_item($c, $read_only = false, $is_reply = false) {
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
                        <img src="<?= htmlspecialchars($avatar) ?>" class="comment-avatar" alt="<?= htmlspecialchars($c['user_name']) ?>" onerror="this.src='<?= $default_avatar ?>'">
                        <div class="online-dot"></div>
                    </div>
                    <div class="comment-meta">
                        <span class="comment-author">
                            <?= htmlspecialchars($c['user_name']) ?>
                            <span class="user-level-badge level-<?= $c['user_level'] ?>">سطح <?= $c['user_level'] ?></span>
                        </span>
                        <?php if (isset($c['target_info']) && $c['target_info']): ?>
                            <span class="text-gray-400 font-size-0-8 mx-1">در</span>
                            <a href="<?= $c['target_info']['url'] ?>" class="text-primary hover-underline d-inline-block ltr font-size-0-8"><?= htmlspecialchars($c['target_info']['title']) ?></a>
                        <?php endif; ?>
                        <span class="comment-date"><?= jalali_date($c['created_at']) ?></span>
                    </div>
                </div>
                <div class="header-actions">
                    <?php if ($c['can_edit']): ?>
                        <div class="comment-header-btn edit-btn" title="ویرایش" data-id="<?= $c['id'] ?>"><i data-lucide="edit-3" class="icon-size-4"></i></div>
                    <?php endif; ?>
                    <div class="comment-header-btn report-btn" title="گزارش تخلف" data-id="<?= $c['id'] ?>"><i data-lucide="flag" class="icon-size-4"></i></div>
                    <div class="comment-header-btn comment-share-btn" title="کپی لینک مستقیم" data-id="<?= $c['id'] ?>">
                        <i data-lucide="share-2" class="icon-size-4"></i>
                    </div>
                </div>
            </div>

            <div class="comment-content">
                <?php if (!empty($c['reply_to_content'])): ?>
                    <div class="reply-preview-block">
                        <div class="">در پاسخ به <span class="reply-preview-author">@<?= htmlspecialchars($c['reply_to_username'] ?? 'user') ?></span></div>
                        <div class="reply-preview-content"><?= htmlspecialchars(mb_substr($c['reply_to_content'], 0, 100)) . (mb_strlen($c['reply_to_content']) > 100 ? '...' : '') ?></div>
                    </div>
                <?php endif; ?>
                <div class="comment-body-text"><?= $c['content_html'] ?></div>
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
                <?php endif; ?>

                <div class="footer-right">
                    <div class="reaction-pill">
                        <?= render_reaction($c, 'like', '👍') ?>
                        <?= render_reaction($c, 'heart', '❤️') ?>
                        <?= render_reaction($c, 'fire', '🔥') ?>
                        <?= render_reaction($c, 'dislike', '👎') ?>
                    </div>
                    <div class="comment-footer-btn btn-react-trigger" data-id="<?= $c['id'] ?>">
                        <i data-lucide="smile" class="icon-size-4"></i>
                        <span>واکنش</span>
                    </div>
                </div>

                <div class="reactions-popover" id="popover-<?= $c['id'] ?>">
                    <span class="emoji-btn" data-id="<?= $c['id'] ?>" data-type="like">👍</span>
                    <span class="emoji-btn" data-id="<?= $c['id'] ?>" data-type="heart">❤️</span>
                    <span class="emoji-btn" data-id="<?= $c['id'] ?>" data-type="fire">🔥</span>
                    <span class="emoji-btn" data-id="<?= $c['id'] ?>" data-type="dislike">👎</span>
                </div>
            </div>
        </div>

        <div id="reply-form-container-<?= $c['id'] ?>"></div>

        <?php if ($has_replies): ?>
            <div class="replies-container" id="replies-container-<?= $c['id'] ?>">
                <div class="replies-list">
                    <?php if (!empty($c['replies'])) foreach ($c['replies'] as $reply) echo render_comment_item($reply, $read_only, true); ?>
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

<div class="comments-section <?= $read_only ? 'read-only' : '' ?>" id="comments-app" data-target-id="<?= $target_id ?>" data-target-type="<?= $target_type ?>">
    <?php if (!$read_only): ?>
        <div class="comments-header">
            <i data-lucide="message-square" class="text-primary w-6 h-6"></i>
            <h3>نظرات کاربران <span class="comments-count-badge">(<?= fa_num($total_count ?? 0) ?>)</span></h3>
        </div>

        <?php if ($is_logged_in): ?>
            <div class="comment-form" id="form-main">
                <textarea placeholder="دیدگاه تخصصی خود را اینجا بنویسید (استفاده از @ برای منشن)..." id="textarea-main"></textarea>
                <div class="comment-form-footer">
                    <div></div>
                    <button class="btn btn-primary submit-comment radius-10" data-parent="">ارسال نظر</button>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-orange-light pd-md radius-16 border mb-2 border-orange d-flex-wrap just-between align-center gap-1">
                <p class="font-bold text-orange">برای ثبت نظر و کسب امتیاز باید وارد حساب خود شوید</p>
                <div class="d-flex gap-1">
                    <button class="btn btn-orange btn-sm" onclick="window.showAuthModal?.('login')">ورود به حساب</button>
                    <button class="btn btn-secondary btn-sm bg-block" onclick="window.showAuthModal?.('register')">عضویت رایگان</button>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="comment-list <?= $read_only ? 'mt-0' : 'mt-8' ?>">
        <?php if (empty($comments)): ?>
            <div class="bg-block text-center pd-md radius-16 border d-column just-center align-center">
                <i data-lucide="message-circle" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                <p class="text-gray-400"><?= $read_only ? 'هنوز نظری ثبت نشده است.' : 'هنوز نظری ثبت نشده است. اولین تحلیل‌گر باشید!' ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($comments as $c) echo render_comment_item($c, $read_only, false); ?>
        <?php endif; ?>
    </div>

    <?php if (($total_pages ?? 1) > 1): ?>
        <div class="pagination mt-3 d-flex just-center gap-05">
            <?php
            $current_url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $query = $_GET;
            for ($i = 1; $i <= $total_pages; $i++):
                $query['page'] = $i;
                $url = $current_url . '?' . http_build_query($query) . '#comments-app';
            ?>
                <a href="<?= htmlspecialchars($url) ?>" class="btn <?= $i == ($current_page ?? 1) ? 'btn-primary' : 'btn-secondary' ?> btn-sm radius-8"><?= fa_num($i) ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

    <script>
        window.__COMMENTS_INITIAL_DATA__ = window.__COMMENTS_INITIAL_DATA__ || {};
        window.__COMMENTS_INITIAL_DATA__['<?= $target_type . '_' . $target_id ?>'] = {
            comments: <?= json_encode($comments, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            total_count: <?= (int)($total_count ?? 0) ?>
        };
    </script>
</div>
