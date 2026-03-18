<div class="section">
    <div class="d-flex-wrap gap-md align-stretch profile-container max-w-1000 mx-auto">
        <!-- Sidebar Navigation -->
        <div class="basis-250 grow-1">
            <!-- stats info -->
            <div class="profile-card p-1 bg-block border radius-16 overflow-hidden sticky-card-2">
                <div class="py-1 border-bottom d-flex just-between align-center">
                    <strong class="font-size-2 font-bold m-0"><i data-lucide="bar-chart-2" class="icon-size-4 d-inline-block v-middle ml-05"></i>آمار کلی شبکه</strong>
                </div>

                <div class="profile-body p-1 pt-0 mt-1">
                    <div class="profile-stats d-flex just-around text-center py-1 gap-1">
                        <div class="stat-item basis-100">
                            <div class="stat-value font-size-3 font-bold"><?= fa_num($stats['users'] ?? 0) ?></div>
                            <div class="stat-label text-gray font-size-1">کاربر</div>
                        </div>
                        <div class="stat-item basis-100">
                            <div class="stat-value font-size-3 font-bold text-success"><?= fa_num($stats['analyses'] ?? 0) ?></div>
                            <div class="stat-label text-gray font-size-1">تحلیل</div>
                        </div>
                        <div class="stat-item basis-100">
                            <div class="stat-value font-size-3 font-bold"><?= fa_num($stats['comments'] ?? 0) ?></div>
                            <div class="stat-label text-gray font-size-1">نظر</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- suggested users tab -->
            <?php if (!empty($suggested_users)): ?>
            <div class="bg-block pd-md border radius-16 sticky-card-2 mt-2">
                <div class="py-1 border-bottom mb-1 d-flex just-between align-center">
                    <strong class="font-size-2 font-bold m-0"><i data-lucide="users" class="icon-size-4 d-inline-block v-middle ml-05"></i>پیشنهاد برای دنبال کردن</strong>
                </div>
                <div class="d-column gap-05">
                    <?php foreach ($suggested_users as $u): ?>
                        <a href="/profile/<?= $u['id'] ?>/<?= urlencode($u['username']) ?>" class="user-row">
                            <img src="<?= htmlspecialchars($u['avatar'] ?: '/assets/images/default-avatar.png') ?>" alt="<?= htmlspecialchars($u['name']) ?>" onerror="this.src='/assets/images/default-avatar.png'">
                            <div class="grow-1 line-height-2">
                                <div class="font-bold text-title font-size-1"><?= htmlspecialchars($u['name']) ?> <span class="text-primary font-size-0-8 mr-1"><i data-lucide="award" class="icon-size-3 v-middle"></i> <?= fa_num($u['level']) ?></span></div>
                                <div class="text-gray font-size-0-8 d-inline-block ltr">@<?= htmlspecialchars($u['username']) ?></div>
                            </div>
                            <i data-lucide="chevron-left" class="text-gray icon-size-4"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <script>
            const stickyManagerTwo = initMultipleSticky('.sticky-card-2', {
                offset: 20,
                minContainerWidth: 760,
                container: '.profile-container',
                gap: 20,
                zIndex: 1
            });
        </script>

        <!-- Main Content Feed (Twitter Style) -->
        <div class="basis-500 grow-9 overflow-hidden">
            <div class="py-1 border-bottom bg-secondary sticky top-0 z-10 px-2 radius-t-16">
                <strong class="font-size-4 font-bold m-0">اکسپلور</strong>
                <p class="text-gray font-size-1 mt-05 mb-0">جدیدترین تحلیل‌ها و نظرات کاربران</p>
            </div>
            <div id="explore-feed" class="py-2 px-2 bg-block radius-b-16 min-h-400">
                <?php
                // We use the CommentSystem to render the explore feed.
                // We mock target_type='explore' so the component doesn't filter by a specific item.
                // We'll also need to ensure the JS and PHP handlers support this.
                $comments = $explore_comments ?? [];
                $total_count = $explore_comments_data['total_count'] ?? 0;
                $total_pages = $explore_comments_data['total_pages'] ?? 1;
                $current_page = $explore_comments_data['current_page'] ?? 1;
                $target_id = 0;
                $target_type = 'explore';
                $read_only = true;
                include __DIR__ . '/../components/comments.php';
                ?>
            </div>
        </div>
    </div>
</div>

<style>
    .user-row {
        display: flex; align-items: center; gap: 0.5rem; padding: 6px 8px; border-radius: 12px;
        transition: background 0.2s; text-decoration: none; color: inherit;
    }
    .user-row:hover { background: var(--color-secondary); }
    .user-row img { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 1px solid var(--color-border); }
    .min-h-400 { min-height: 400px; }
</style>

<link rel="stylesheet" href="<?= versioned_asset('/assets/css/comments.css') ?>">
<script src="<?= versioned_asset('/assets/js/comments.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    new CommentSystem({ containerId: 'comments-app' });
});
</script>
