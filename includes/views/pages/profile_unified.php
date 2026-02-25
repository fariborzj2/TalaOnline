<?php
// Refresh verification status from DB if owner
global $pdo;
if ($is_owner && isset($pdo) && $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT is_verified, is_phone_verified, email, phone FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $db_user = $stmt->fetch();
        if ($db_user) {
            $_SESSION['is_verified'] = (int)$db_user['is_verified'];
            $_SESSION['is_phone_verified'] = (int)$db_user['is_phone_verified'];
            $_SESSION['user_email'] = $db_user['email'];
            $_SESSION['user_phone'] = $db_user['phone'];
        }
    } catch (Exception $e) {}
}

$email_unverified = $is_owner && empty($_SESSION['is_verified']);
$phone_unverified = $is_owner && (get_setting('mobile_verification_enabled') === '1' && empty($_SESSION['is_phone_verified']));
?>

<div class="section">
    <div class="bg-block pd-md border radius-16 d-flex align-center gap-1-5">
        <div class="relative shrink-0">
            <div class="w-24 h-24 radius-50 bg-secondary d-flex align-center just-center border overflow-hidden">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="<?= htmlspecialchars($user['name']) ?>" class="w-full h-full object-cover" id="profile-avatar-img">
                <?php else: ?>
                    <i data-lucide="user" class="icon-size-8 text-primary" id="profile-avatar-icon"></i>
                <?php endif; ?>
            </div>
            <?php if ($is_owner): ?>
                <button class="btn-avatar-upload" onclick="document.getElementById('avatar-input').click()" title="تغییر تصویر پروفایل">
                    <i data-lucide="camera" class="icon-size-3"></i>
                </button>
                <input type="file" id="avatar-input" class="d-none" accept="image/jpeg,image/png,image/webp">
            <?php endif; ?>
        </div>
        <div class="grow-1">
            <div class="d-flex just-between align-start flex-wrap gap-1">
                <div>
                    <h1 class="font-size-4 font-black text-title"><?= htmlspecialchars($user['name']) ?></h1>
                    <div class="d-flex align-center gap-1 mt-05 flex-wrap">
                        <span class="bg-secondary px-1 radius-8 border text-gray font-bold">@<?= htmlspecialchars($user['username']) ?></span>
                        <span class="bg-primary px-1 radius-8 text-white font-bold">سطح <?= $user['level'] ?></span>
                        <?php if ($user['level'] >= 5): ?>
                            <span class="bg-orange-light px-1 radius-8 text-orange font-bold d-flex align-center gap-05">
                                <i data-lucide="award" class="icon-size-4"></i>
                                تحلیل‌گر برتر
                            </span>
                        <?php elseif ($user['level'] >= 3): ?>
                            <span class="bg-success-light px-1 radius-8 text-success font-bold d-flex align-center gap-05">
                                <i data-lucide="check-circle" class="icon-size-4"></i>
                                کاربر فعال
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!$is_owner && isset($_SESSION['user_id'])): ?>
                    <button id="follow-btn" class="btn <?= $is_following ? 'btn-secondary' : 'btn-primary' ?> radius-12 px-2 d-flex align-center gap-05" data-user-id="<?= $user['id'] ?>">
                        <i data-lucide="<?= $is_following ? 'user-minus' : 'user-plus' ?>" class="icon-size-4"></i>
                        <span><?= $is_following ? 'لغو دنبال کردن' : 'دنبال کردن' ?></span>
                    </button>
                <?php endif; ?>
            </div>

            <div class="d-flex align-center gap-2 mt-1-5 flex-wrap">
                <div class="pointer hover-text-primary transition-all" id="followers-trigger">
                    <strong class="text-title font-size-1-2" id="follower-count"><?= fa_num($follower_count) ?></strong>
                    <span class="text-gray font-size-0-9">دنبال‌کننده</span>
                </div>
                <div class="pointer hover-text-primary transition-all" id="following-trigger">
                    <strong class="text-title font-size-1-2" id="following-count"><?= fa_num($following_count) ?></strong>
                    <span class="text-gray font-size-0-9">دنبال‌شونده</span>
                </div>
                <div class="mr-auto text-gray font-size-0-9">عضویت از: <?= jalali_date($user['created_at'], 'compact') ?></div>
            </div>
        </div>
    </div>
</div>

<div class="section">
    <div class="d-flex-wrap gap-md align-stretch">
        <!-- Sidebar Navigation -->
        <div class="basis-250 grow-1">
            <div class="bg-block pd-md border radius-16 is-sticky">
                <div class="d-column gap-05" id="profile-tabs">
                    <button class="profile-tab-btn active" data-tab="activity">
                        <i data-lucide="activity"></i> فعالیت‌ها
                    </button>
                    <?php if ($is_owner): ?>
                        <div class="divider my-1"></div>
                        <button class="profile-tab-btn" data-tab="edit">
                            <i data-lucide="user-cog"></i> ویرایش پروفایل
                        </button>
                        <button class="profile-tab-btn" data-tab="security">
                            <i data-lucide="shield-check"></i> امنیت و رمز عبور
                        </button>
                        <div class="divider my-1"></div>
                        <button class="profile-tab-btn text-error" id="profile-logout-btn">
                            <i data-lucide="log-out"></i> خروج از حساب
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="basis-500 grow-9">
            <div class="bg-block border radius-16 min-h-400">
                <!-- Activity Tab (User's Comments) -->
                <div id="tab-activity" class="profile-tab-content pd-md">
                    <h2 class="font-size-3 font-black mb-2 border-bottom pb-1">آخرین دیدگاه‌ها</h2>
                    <div id="user-comments-list" class="comment-list mt-1">
                        <?php if (empty($user_comments)): ?>
                            <div class="text-center py-12 bg-gray-50 radius-24 border border-dashed">
                                <i data-lucide="message-circle" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                                <p class="text-gray-400">هنوز نظری ثبت نشده است.</p>
                            </div>
                        <?php else: ?>
                            <div id="profile-comments-app"></div>
                            <script>
                                window.__PROFILE_COMMENTS_DATA__ = <?= json_encode($user_comments, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
                            </script>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($is_owner): ?>
                    <!-- Edit Profile Tab -->
                    <div id="tab-edit" class="profile-tab-content d-none pd-md">
                        <h2 class="font-size-3 font-black mb-2 border-bottom pb-1">ویرایش اطلاعات کاربری</h2>

                        <?php if ($email_unverified): ?>
                            <div class="bg-error-light pd-md radius-12 border border-error mb-2 d-flex gap-1 align-center">
                                <i data-lucide="alert-circle" class="text-error shrink-0"></i>
                                <div class="grow-1">
                                    <p class="text-error font-bold mb-05">ایمیل شما هنوز تایید نشده است.</p>
                                    <button id="resend-verification-btn" class="btn btn-error btn-sm radius-8">ارسال مجدد ایمیل تایید</button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form id="profile-update-form" class="d-column gap-1-5 mt-2">
                             <div class="d-column gap-05">
                                <label class="font-bold pr-1 font-size-1">نام و نام خانوادگی</label>
                                <div class="input-item">
                                    <i data-lucide="user" class="text-gray icon-size-3"></i>
                                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                                </div>
                            </div>
                            <div class="d-column gap-05">
                                <label class="font-bold pr-1 font-size-1">نام کاربری (Username)</label>
                                <div class="input-item">
                                    <i data-lucide="at-sign" class="text-gray icon-size-3"></i>
                                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" dir="ltr" class="text-left" required>
                                </div>
                                <small class="text-gray pr-1 font-size-0-8">فقط حروف انگلیسی و اعداد و (_) مجاز است (۳ تا ۳۰ کاراکتر).</small>
                            </div>
                            <div class="d-column gap-05">
                                <label class="font-bold pr-1 font-size-1">آدرس ایمیل</label>
                                <div class="input-item">
                                    <i data-lucide="mail" class="text-gray icon-size-3"></i>
                                    <input type="email" name="email" value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>" dir="ltr" class="text-left" required>
                                </div>
                            </div>
                            <div class="d-column gap-05">
                                <label class="font-bold pr-1 font-size-1">شماره موبایل</label>
                                <div class="input-item">
                                    <i data-lucide="phone" class="text-gray icon-size-3"></i>
                                    <input type="text" name="phone" value="<?= htmlspecialchars($_SESSION['user_phone'] ?? '') ?>" dir="ltr" class="text-left">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary radius-12 just-center mt-1">ذخیره تغییرات</button>
                        </form>
                    </div>

                    <!-- Security Tab -->
                    <div id="tab-security" class="profile-tab-content d-none pd-md">
                        <h2 class="font-size-3 font-black mb-2 border-bottom pb-1">امنیت و رمز عبور</h2>

                        <?php if ($phone_unverified): ?>
                            <div class="bg-orange-light pd-md radius-12 border border-orange mb-2 d-flex gap-1 align-center">
                                <i data-lucide="smartphone" class="text-orange shrink-0"></i>
                                <div class="grow-1">
                                    <p class="text-orange font-bold mb-05">شماره موبایل شما تایید نشده است.</p>
                                    <p class="font-size-0-9 mb-1">لطفاً کد تایید ارسال شده به شماره <strong><?= htmlspecialchars($_SESSION['user_phone'] ?? 'نامشخص') ?></strong> را وارد کنید.</p>
                                    <div class="d-flex gap-05 mt-1">
                                        <div class="input-item grow-1">
                                            <input type="text" id="phone-verification-code" placeholder="کد ۵ رقمی" class="text-center font-bold ltr" maxlength="10">
                                        </div>
                                        <button id="verify-phone-btn" class="btn btn-primary btn-sm radius-8">تایید شماره</button>
                                        <button id="resend-sms-btn" class="btn btn-secondary btn-sm radius-8">ارسال مجدد</button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <h3 class="font-size-2 font-bold mb-1 mt-2">تغییر رمز عبور</h3>
                        <form id="password-update-form" class="d-column gap-1-5 mt-1">
                             <div class="d-column gap-05">
                                <label class="font-bold pr-1 font-size-1">رمز عبور فعلی</label>
                                <div class="input-item">
                                    <i data-lucide="lock" class="text-gray icon-size-3"></i>
                                    <input type="password" id="current_password" name="current_password" required>
                                    <button type="button" class="pointer text-gray hover-text-primary" onclick="togglePassword('current_password', this)">
                                        <i data-lucide="eye" class="icon-size-3"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="d-column gap-05">
                                <label class="font-bold pr-1 font-size-1">رمز عبور جدید</label>
                                <div class="input-item">
                                    <i data-lucide="key" class="text-gray icon-size-3"></i>
                                    <input type="password" id="new_password" name="new_password" required>
                                    <button type="button" class="pointer text-gray hover-text-primary" onclick="togglePassword('new_password', this)">
                                        <i data-lucide="eye" class="icon-size-3"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="d-column gap-05">
                                <label class="font-bold pr-1 font-size-1">تکرار رمز عبور جدید</label>
                                <div class="input-item">
                                    <i data-lucide="check-circle" class="text-gray icon-size-3"></i>
                                    <input type="password" id="confirm_password" name="confirm_password" required>
                                    <button type="button" class="pointer text-gray hover-text-primary" onclick="togglePassword('confirm_password', this)">
                                        <i data-lucide="eye" class="icon-size-3"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary radius-12 just-center mt-1">بروزرسانی رمز عبور</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Followers Modal -->
<div id="followers-modal" class="modal-overlay d-none">
    <div class="modal-content bg-block radius-24 shadow-lg overflow-hidden basis-400">
        <div class="pd-md border-bottom d-flex just-between align-center">
            <h3 class="font-bold font-size-4">دنبال‌کنندگان</h3>
            <button class="close-modal pointer"><i data-lucide="x" class="icon-size-4"></i></button>
        </div>
        <div class="pd-md overflow-y-auto max-h-400" id="followers-list">
            <div class="text-center py-8"><i data-lucide="loader-2" class="spin text-primary"></i></div>
        </div>
    </div>
</div>

<!-- Following Modal -->
<div id="following-modal" class="modal-overlay d-none">
    <div class="modal-content bg-block radius-24 shadow-lg overflow-hidden basis-400">
        <div class="pd-md border-bottom d-flex just-between align-center">
            <h3 class="font-bold font-size-4">دنبال‌شوندگان</h3>
            <button class="close-modal pointer"><i data-lucide="x" class="icon-size-4"></i></button>
        </div>
        <div class="pd-md overflow-y-auto max-h-400" id="following-list">
            <div class="text-center py-8"><i data-lucide="loader-2" class="spin text-primary"></i></div>
        </div>
    </div>
</div>

<style>
    .profile-tab-btn {
        display: flex; align-items: center; gap: 12px; padding: 14px 20px; border-radius: 12px;
        font-weight: 600; font-size: 14px; color: var(--color-gray); transition: all 0.2s;
        width: 100%; text-align: right; background: transparent; border: none; cursor: pointer;
    }
    .profile-tab-btn:hover { background: var(--color-secondary); color: var(--color-title); }
    .profile-tab-btn.active { background: var(--color-primary-light); color: var(--color-primary); }
    .min-h-400 { min-height: 400px; }
    .max-h-400 { max-height: 400px; }
    .btn-avatar-upload {
        position: absolute; bottom: 0; left: 0; background: var(--color-primary); color: white;
        width: 32px; height: 32px; border-radius: 50%; border: 3px solid white;
        display: flex; align-items: center; justify-content: center; cursor: pointer;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    }
    .user-row {
        display: flex; align-items: center; gap: 1rem; padding: 0.75rem; border-radius: 12px;
        transition: background 0.2s; text-decoration: none; color: inherit;
    }
    .user-row:hover { background: var(--color-secondary); }
    .user-row img { width: 40px; height: 40px; border-radius: 50%; object-cover: cover; }
</style>

<script src="<?= versioned_asset('/assets/js/comments.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Initialize profile comments if they exist
    if (window.__PROFILE_COMMENTS_DATA__) {
        const app = new CommentSystem({
            containerId: 'profile-comments-app',
            targetId: '<?= $user['id'] ?>',
            targetType: 'user_profile',
            initialComments: window.__PROFILE_COMMENTS_DATA__
        });
        // Hide form on profile comments
        const form = document.querySelector('#profile-comments-app .comment-form');
        if (form) form.classList.add('d-none');
    }
});
</script>
