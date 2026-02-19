<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
check_login();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blog_main_title = $_POST['blog_main_title'];
    $blog_main_description = $_POST['blog_main_description'];
    $blog_main_keywords = $_POST['blog_main_keywords'];
    $blog_show_views = isset($_POST['blog_show_views']) ? '1' : '0';
    $blog_show_reading_time = isset($_POST['blog_show_reading_time']) ? '1' : '0';
    $blog_related_count = $_POST['blog_related_count'];
    $blog_posts_per_page = $_POST['blog_posts_per_page'];

    set_setting('blog_main_title', $blog_main_title);
    set_setting('blog_main_description', $blog_main_description);
    set_setting('blog_main_keywords', $blog_main_keywords);
    set_setting('blog_show_views', $blog_show_views);
    set_setting('blog_show_reading_time', $blog_show_reading_time);
    set_setting('blog_related_count', $blog_related_count);
    set_setting('blog_posts_per_page', $blog_posts_per_page);

    $message = 'تنظیمات وبلاگ با موفقیت ذخیره شد.';
}

$blog_main_title = get_setting('blog_main_title', 'وبلاگ و اخبار طلا و ارز');
$blog_main_description = get_setting('blog_main_description', 'آخرین اخبار، مقالات تخصصی و تحلیل‌های بازار طلا، سکه و ارز را در وبلاگ طلا آنلاین بخوانید.');
$blog_main_keywords = get_setting('blog_main_keywords', 'اخبار طلا, تحلیل بازار, مقالات آموزشی طلا');
$blog_show_views = get_setting('blog_show_views', '1');
$blog_show_reading_time = get_setting('blog_show_reading_time', '1');
$blog_related_count = get_setting('blog_related_count', '3');
$blog_posts_per_page = get_setting('blog_posts_per_page', '10');

$page_title = 'تنظیمات وبلاگ';
$page_subtitle = 'مدیریت سئو صفحه اصلی وبلاگ و تنظیمات نمایش مقالات';

include __DIR__ . '/layout/header.php';
?>

<?php if ($message): ?>
    <div class="mb-8 animate-bounce-in">
        <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 flex items-center gap-3 text-emerald-700">
            <div class="w-8 h-8 bg-emerald-500 text-white rounded-lg flex items-center justify-center">
                <i data-lucide="check" class="w-5 h-5"></i>
            </div>
            <span class="font-bold"><?= $message ?></span>
        </div>
    </div>
<?php endif; ?>

<form method="POST" class="max-w-4xl space-y-8 pb-10">
    <!-- Blog SEO Settings -->
    <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
        <div class="px-8 py-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/30">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-indigo-600 border border-indigo-50">
                <i data-lucide="search" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-lg font-black text-slate-800">سئو صفحه اصلی وبلاگ</h2>
                <p class="text-[10px] text-slate-400 font-bold uppercase ">Blog Main SEO</p>
            </div>
        </div>
        <div class="p-8 space-y-6">
            <div class="form-group">
                <label>عنوان صفحه وبلاگ</label>
                <div class="input-icon-wrapper">
                    <span class="icon"><i data-lucide="type" class="w-4 h-4"></i></span>
                    <input type="text" name="blog_main_title" value="<?= htmlspecialchars($blog_main_title) ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label>توضیحات متا وبلاگ</label>
                <textarea name="blog_main_description" rows="3" class="resize-none"><?= htmlspecialchars($blog_main_description) ?></textarea>
            </div>
            <div class="form-group">
                <label>کلمات کلیدی وبلاگ</label>
                <div class="input-icon-wrapper">
                    <span class="icon"><i data-lucide="hash" class="w-4 h-4"></i></span>
                    <input type="text" name="blog_main_keywords" value="<?= htmlspecialchars($blog_main_keywords) ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Display Settings -->
    <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
        <div class="px-8 py-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/30">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-emerald-600 border border-emerald-50">
                <i data-lucide="layout" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-lg font-black text-slate-800">تنظیمات نمایش</h2>
                <p class="text-[10px] text-slate-400 font-bold uppercase ">Display Configuration</p>
            </div>
        </div>
        <div class="p-8 space-y-6">
            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
                <div>
                    <h4 class="font-black text-slate-700 text-sm">نمایش تعداد بازدید</h4>
                    <p class="text-[10px] text-slate-400 font-bold">نمایش آمار بازدید در صفحات مقاله</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="blog_show_views" class="sr-only peer" <?= $blog_show_views == '1' ? 'checked' : '' ?>>
                    <div class="toggle-dot"></div>
                </label>
            </div>

            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
                <div>
                    <h4 class="font-black text-slate-700 text-sm">نمایش زمان مطالعه</h4>
                    <p class="text-[10px] text-slate-400 font-bold">تخمین خودکار زمان مورد نیاز برای خواندن مقاله</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="blog_show_reading_time" class="sr-only peer" <?= $blog_show_reading_time == '1' ? 'checked' : '' ?>>
                    <div class="toggle-dot"></div>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label>تعداد مقالات در هر صفحه</label>
                    <div class="input-icon-wrapper">
                        <span class="icon"><i data-lucide="list-ordered" class="w-4 h-4"></i></span>
                        <input type="number" name="blog_posts_per_page" value="<?= htmlspecialchars($blog_posts_per_page) ?>" min="1" max="50" required class="ltr-input">
                    </div>
                    <p class="text-[10px] text-slate-400 mt-2 font-bold uppercase ">تعداد مقالات نمایشی در صفحات لیست وبلاگ</p>
                </div>

                <div class="form-group">
                    <label>تعداد مطالب مرتبط</label>
                    <div class="input-icon-wrapper">
                        <span class="icon"><i data-lucide="layers-3" class="w-4 h-4"></i></span>
                        <input type="number" name="blog_related_count" value="<?= htmlspecialchars($blog_related_count) ?>" min="0" max="10" required class="ltr-input">
                    </div>
                    <p class="text-[10px] text-slate-400 mt-2 font-bold uppercase ">تعداد مقالات پیشنهادی در انتهای هر نوشته</p>
                </div>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-end gap-4 pt-4">
        <button type="submit" class="btn-v3 btn-v3-primary w-full md:w-auto min-w-[200px]">
            <i data-lucide="save" class="w-5 h-5"></i>
            ذخیره تنظیمات وبلاگ
        </button>
    </div>
</form>

<?php include __DIR__ . '/layout/footer.php'; ?>
