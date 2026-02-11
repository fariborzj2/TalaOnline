<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
check_login();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['about_us_content'] ?? '';
    if (set_setting('about_us_content', $content)) {
        $message = 'محتوای صفحه «درباره ما» با موفقیت بروزرسانی شد.';
    } else {
        $error = 'خطا در ذخیره‌سازی اطلاعات.';
    }
}

$about_us_content = get_setting('about_us_content', 'لطفاً محتوای این صفحه را از پنل مدیریت تنظیم کنید.');

$page_title = 'مدیریت صفحه درباره ما';
$page_subtitle = 'ویرایش محتوای متنی، تصاویر و اطلاعات تماس در صفحه درباره ما';

include __DIR__ . '/layout/header.php';
?>

<!-- TinyMCE CDN -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<script>
  tinymce.init({
    selector: '#about_us_editor',
    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
    directionality: 'rtl',
    language: 'fa',
    height: 500,
    branding: false,
    promotion: false,
    content_style: 'body { font-family:Vazirmatn,Arial,sans-serif; font-size:14px; direction: rtl; }'
  });
</script>

<?php if ($message): ?>
    <div class="mb-6">
        <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-4 flex items-center gap-3 text-emerald-700">
            <div class="w-8 h-8 bg-emerald-500 text-white rounded-lg flex items-center justify-center">
                <i data-lucide="check" class="w-5 h-5"></i>
            </div>
            <span class="font-bold"><?= $message ?></span>
        </div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="mb-6">
        <div class="bg-rose-50 border border-rose-100 rounded-lg p-4 flex items-center gap-3 text-rose-700">
            <div class="w-8 h-8 bg-rose-500 text-white rounded-lg flex items-center justify-center">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
            </div>
            <span class="font-bold"><?= $error ?></span>
        </div>
    </div>
<?php endif; ?>

<div class="glass-card rounded-xl overflow-hidden border border-slate-200">
    <div class="px-8 py-6 border-b border-slate-100 flex items-center gap-3 bg-slate-50/30">
        <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-slate-400 border border-slate-100">
            <i data-lucide="book-open" class="w-5 h-5"></i>
        </div>
        <h2 class="text-lg font-black text-slate-800">ویرایشگر محتوا</h2>
    </div>
    <div class="p-8">
        <form method="POST">
            <div class="form-group mb-6">
                <label class="mb-4">محتوای متنی صفحه</label>
                <textarea name="about_us_content" id="about_us_editor"><?= htmlspecialchars($about_us_content) ?></textarea>
            </div>

            <div class="flex items-center justify-end gap-4">
                <button type="submit" class="btn-v3 btn-v3-primary min-w-[180px]">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    ذخیره تغییرات صفحه
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
