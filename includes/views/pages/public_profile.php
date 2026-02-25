<div class="section">
    <div class="bg-block pd-md border radius-16 d-flex align-center gap-1-5">
        <div class="w-24 h-24 radius-50 bg-secondary d-flex align-center just-center border overflow-hidden">
            <?php if (!empty($user['avatar'])): ?>
                <img src="/<?= ltrim($user['avatar'], '/') ?>" alt="<?= htmlspecialchars($user['name']) ?>" class="w-full h-full object-cover">
            <?php else: ?>
                <i data-lucide="user" class="icon-size-8 text-primary"></i>
            <?php endif; ?>
        </div>
        <div class="grow-1">
            <h1 class="font-size-4 font-black text-title"><?= htmlspecialchars($user['name']) ?></h1>
            <div class="d-flex align-center gap-1 mt-05">
                <span class="bg-secondary px-1 radius-8 border text-gray font-bold">@<?= htmlspecialchars($user['username']) ?></span>
                <span class="bg-primary px-1 radius-8 text-white font-bold">سطح <?= $user['level'] ?></span>
            </div>
            <p class="text-gray font-size-0-9 mt-1">عضویت از: <?= jalali_date($user['created_at'], 'compact') ?></p>
        </div>
    </div>
</div>

<div class="section">
    <div class="bg-block pd-md border radius-16">
        <h2 class="font-size-2 font-black mb-1">درباره کاربر</h2>
        <p class="text-subtitle">این کاربر در حال حاضر توضیحی برای خود ثبت نکرده است.</p>
    </div>
</div>
