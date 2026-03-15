<div class="section">
    <div class="d-flex-wrap gap-md align-stretch profile-container">
        <!-- Main Content Feed (Twitter Style) -->
        <div class="basis-600 grow-1 max-w-800 mx-auto bg-block border radius-16 overflow-hidden">
            <div class="pd-md border-bottom bg-secondary sticky top-0 z-10">
                <h1 class="font-size-4 font-bold m-0">اکسپلور</h1>
                <p class="text-gray font-size-1 mt-05 mb-0">جدیدترین تحلیل‌ها و نظرات کاربران</p>
            </div>
            <div id="explore-feed" class="pd-md">
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
                $read_only = false;
                include __DIR__ . '/../components/comments.php';
                ?>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="<?= versioned_asset('/assets/css/comments.css') ?>">
<script src="<?= versioned_asset('/assets/js/comments.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    new CommentSystem({ containerId: 'comments-app' });
});
</script>
