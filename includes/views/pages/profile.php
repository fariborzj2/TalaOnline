<div class="section">
    <div class="bg-block pd-md border radius-16 d-flex align-center gap-1-5">
        <div class="relative shrink-0">
            <div class="w-20 h-20 radius-50 bg-secondary d-flex align-center just-center border overflow-hidden">
                <?php if (!empty($_SESSION['user_avatar'])): ?>
                    <img src="<?= htmlspecialchars($_SESSION['user_avatar']) ?>" alt="<?= htmlspecialchars($_SESSION['user_name']) ?>" class="w-full h-full object-cover" id="profile-avatar-img">
                <?php else: ?>
                    <i data-lucide="user" class="icon-size-8 text-primary" id="profile-avatar-icon"></i>
                <?php endif; ?>
            </div>
            <button class="btn-avatar-upload" onclick="document.getElementById('avatar-input').click()" title="تغییر تصویر پروفایل">
                <i data-lucide="camera" class="icon-size-3"></i>
            </button>
            <input type="file" id="avatar-input" class="d-none" accept="image/jpeg,image/png,image/webp">
        </div>
        <div class="grow-1">
            <h1 class="font-size-4 font-black text-title"><?= htmlspecialchars($_SESSION['user_name']) ?></h1>
            <p class="text-gray font-size-1 mt-05">
                <span class="bg-secondary pd-02-05 radius-8 border">@<?= htmlspecialchars($_SESSION['user_username'] ?? '---') ?></span>
                <span class="mx-1">|</span>
                <?= htmlspecialchars($_SESSION['user_email']) ?>
            </p>
        </div>
    </div>
</div>

<div class="section">
    <div class="d-flex-wrap gap-md align-stretch">
        <!-- Profile Sidebar -->
        <div class="basis-250 grow-1">
            <div class="bg-block pd-md border radius-16 is-sticky">
                <div class="d-column gap-05" id="profile-tabs">
                    <button class="profile-tab-btn active" data-tab="overview">
                        <i data-lucide="layout-dashboard"></i> مرور کلی
                    </button>
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
                </div>
            </div>
        </div>

        <!-- Profile Content -->
        <div class="basis-500 grow-9">
            <div class="bg-block border radius-16 min-h-400">
                <!-- Overview Tab -->
                <div id="tab-overview" class="profile-tab-content pd-md">
                    <h2 class="font-size-3 font-black mb-2 border-bottom pb-1">خوش آمدید، <?= htmlspecialchars($_SESSION['user_name']) ?></h2>
                    <div class="grid-2 gap-1 mt-2">
                        <div class="pd-md radius-16 bg-secondary border d-column gap-05">
                            <span class="text-gray font-size-1">وضعیت حساب</span>
                            <strong class="text-success font-size-2">فعال</strong>
                        </div>
                        <div class="pd-md radius-16 bg-secondary border d-column gap-05">
                            <span class="text-gray font-size-1">نام کاربری</span>
                            <strong class="text-title font-size-1-5">@<?= htmlspecialchars($_SESSION['user_username'] ?? '---') ?></strong>
                        </div>
                        <div class="pd-md radius-16 bg-secondary border d-column gap-05">
                            <span class="text-gray font-size-1">شماره تماس</span>
                            <strong class="text-title font-size-1-5 ltr text-right"><?= htmlspecialchars($_SESSION['user_phone'] ?? '---') ?></strong>
                        </div>
                        <div class="pd-md radius-16 bg-secondary border d-column gap-05">
                            <span class="text-gray font-size-1">آخرین فعالیت</span>
                            <strong class="text-title font-size-1-5"><?= jalali_date('now', 'compact') ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Edit Profile Tab -->
                <div id="tab-edit" class="profile-tab-content d-none pd-md">
                    <h2 class="font-size-3 font-black mb-2 border-bottom pb-1">ویرایش اطلاعات کاربری</h2>
                    <form id="profile-update-form" class="d-column gap-1-5 mt-2">
                        <div class="d-column gap-05">
                            <label class="font-bold pr-1 font-size-1">نام و نام خانوادگی</label>
                            <div class="input-item">
                                <i data-lucide="user" class="text-gray icon-size-3"></i>
                                <input type="text" name="name" value="<?= htmlspecialchars($_SESSION['user_name']) ?>" required>
                            </div>
                        </div>
                        <div class="d-column gap-05">
                            <label class="font-bold pr-1 font-size-1">نام کاربری (Username)</label>
                            <div class="input-item">
                                <i data-lucide="at-sign" class="text-gray icon-size-3"></i>
                                <input type="text" name="username" value="<?= htmlspecialchars($_SESSION['user_username'] ?? '') ?>" dir="ltr" class="text-left" placeholder="username" required>
                            </div>
                            <small class="text-gray pr-1 font-size-0-8">فقط حروف، اعداد و (_) مجاز است (۳ تا ۳۰ کاراکتر).</small>
                        </div>
                        <div class="d-column gap-05">
                            <label class="font-bold pr-1 font-size-1">آدرس ایمیل</label>
                            <div class="input-item">
                                <i data-lucide="mail" class="text-gray icon-size-3"></i>
                                <input type="email" name="email" value="<?= htmlspecialchars($_SESSION['user_email']) ?>" dir="ltr" class="text-left" required>
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
                    <h2 class="font-size-3 font-black mb-2 border-bottom pb-1">تغییر رمز عبور</h2>
                    <form id="password-update-form" class="d-column gap-1-5 mt-2">
                        <div class="d-column gap-05">
                            <label class="font-bold pr-1 font-size-1">رمز عبور فعلی</label>
                            <div class="input-item">
                                <i data-lucide="lock" class="text-gray icon-size-3"></i>
                                <input type="password" name="current_password" required>
                            </div>
                        </div>
                        <div class="d-column gap-05">
                            <label class="font-bold pr-1 font-size-1">رمز عبور جدید</label>
                            <div class="input-item">
                                <i data-lucide="key" class="text-gray icon-size-3"></i>
                                <input type="password" name="new_password" required>
                            </div>
                        </div>
                        <div class="d-column gap-05">
                            <label class="font-bold pr-1 font-size-1">تکرار رمز عبور جدید</label>
                            <div class="input-item">
                                <i data-lucide="check-circle" class="text-gray icon-size-3"></i>
                                <input type="password" name="confirm_password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary radius-12 just-center mt-1">بروزرسانی رمز عبور</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .profile-tab-btn {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 20px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 14px;
        color: var(--color-gray);
        transition: all 0.2s;
        width: 100%;
        text-align: right;
        background: transparent;
        border: none;
        cursor: pointer;
    }
    .profile-tab-btn i {
        width: 20px;
        height: 20px;
        transition: transform 0.2s;
    }
    .profile-tab-btn:hover {
        background: var(--color-secondary);
        color: var(--color-title);
    }
    .profile-tab-btn:hover i {
        transform: scale(1.1);
    }
    .profile-tab-btn.active {
        background: var(--color-primary-light);
        color: var(--color-primary);
    }
    .profile-tab-btn.text-error:hover {
        background: var(--bg-error);
        color: var(--color-error);
    }
    .min-h-400 { min-height: 400px; }
</style>
