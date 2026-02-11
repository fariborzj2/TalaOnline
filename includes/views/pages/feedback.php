<div class="section">
    <div class="bg-block pd-md border basis-250 grow-1 radius-16">
        <div class="border-bottom bg-block pd-10">
            <h2 class="font-size-6 font-black text-title">تماس با ما</h2>
            <p class="text-gray font-size-2">نظرات، پیشنهادات و مشکلات خود را با ما در میان بگذارید.</p>
        </div>

        <div >
            <?php if (!empty($message)): ?>
                <div class="pd-md radius-16 mb-2 <?= $success ? 'bg-success text-success border-success' : 'bg-error text-error border-error' ?> d-flex align-center gap-1">
                    <i data-lucide="<?= $success ? 'check-circle' : 'alert-circle' ?>" class="icon-size-4"></i>
                    <strong class="font-size-2"><?= htmlspecialchars($message) ?></strong>
                </div>
            <?php endif; ?>

            <form action="/feedback" method="POST" class="d-flex d-column gap-2">
                <div class="d-flex-wrap gap-2">
                    <div class="d-flex d-column gap-1 flex-grow-1 basis-300">
                        <label class="text-title font-bold pr-1">نام و نام خانوادگی <span class="text-error">*</span></label>
                        <div class="input-item">
                            <i data-lucide="user" class="text-gray icon-size-3"></i>
                            <input type="text" name="name" required placeholder="مثلاً علی علوی">
                        </div>
                    </div>
                    <div class="d-flex d-column gap-1 flex-grow-1 basis-300">
                        <label class="text-title font-bold pr-1">آدرس ایمیل</label>
                        <div class="input-item">
                            <i data-lucide="mail" class="text-gray icon-size-3"></i>
                            <input type="email" name="email" placeholder="example@mail.com" dir="ltr" class="text-left">
                        </div>
                    </div>
                </div>

                <div class="d-flex d-column gap-1">
                    <label class="text-title font-bold pr-1">موضوع پیام</label>
                    <div class="input-item">
                        <i data-lucide="tag" class="text-gray icon-size-3"></i>
                        <input type="text" name="subject" placeholder="موضوع بازخورد شما">
                    </div>
                </div>

                <div class="d-flex d-column gap-1">
                    <label class="text-title font-bold pr-1">متن پیام <span class="text-error">*</span></label>
                    <div class="input-item align-start pt-1">
                        <i data-lucide="message-square" class="text-gray icon-size-3 mt-1"></i>
                        <textarea name="message" required rows="6" placeholder="پیام خود را اینجا بنویسید..." style="width: 100%; border: none; outline: none; background: transparent; font-family: inherit; font-size: 13px; line-height: 24px; resize: vertical;"></textarea>
                    </div>
                </div>

                <div class="mt-1">
                    <button type="submit" class="btn btn-sm btn-primary radius-12 just-center w-full">
                        <i data-lucide="send" class="icon-size-4"></i>
                        <span>ارسال پیام</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .border-success { border: 1px solid rgba(16, 185, 129, 0.3); }
    .border-error { border: 1px solid rgba(239, 68, 68, 0.3); }
    .basis-300 { flex-basis: 300px; }
    textarea::placeholder { color: #ccc; }
</style>
