<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
if (isset($_GET["id"])) { check_permission("blog_categories.edit"); } else { check_permission("blog_categories.create"); }

$id = $_GET['id'] ?? null;
$category = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM blog_categories WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $name = $_POST['name'] ?? '';
        $slug = $_POST['slug'] ?? '';
        $description = $_POST['description'] ?? '';
        $meta_title = $_POST['meta_title'] ?? '';
        $meta_description = $_POST['meta_description'] ?? '';
        $meta_keywords = $_POST['meta_keywords'] ?? '';
        $sort_order = (int)($_POST['sort_order'] ?? 0);

        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE blog_categories SET name = ?, slug = ?, description = ?, meta_title = ?, meta_description = ?, meta_keywords = ?, sort_order = ? WHERE id = ?");
                $stmt->execute([$name, $slug, $description, $meta_title, $meta_description, $meta_keywords, $sort_order, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO blog_categories (name, slug, description, meta_title, meta_description, meta_keywords, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $description, $meta_title, $meta_description, $meta_keywords, $sort_order]);
            }

            header("Location: blog_categories.php?message=success");
            exit;
        } catch (Exception $e) {
            $error = 'خطا در ذخیره اطلاعات: ' . $e->getMessage();
        }
    }
}

$page_title = $id ? 'ویرایش دسته‌بندی وبلاگ' : 'افزودن دسته‌بندی وبلاگ';
$page_subtitle = 'تنظیمات نام و آدرس دسته‌بندی مقالات';

include __DIR__ . '/layout/header.php';
?>

<form method="POST">
    <input type="hidden" name="action" value="save">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Column -->
        <div class="lg:col-span-2 space-y-6">
            <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
                <div class="px-8 py-6 border-b border-slate-100 flex items-center gap-3 bg-slate-50/30">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-slate-400 border border-slate-100">
                        <i data-lucide="settings" class="w-5 h-5"></i>
                    </div>
                    <h2 class="text-lg font-black text-slate-800">اطلاعات پایه</h2>
                </div>
                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="form-group">
                            <label>نام دسته‌بندی</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($category['name'] ?? '') ?>" required placeholder="مثلاً اخبار طلا">
                        </div>
                        <div class="form-group">
                            <label>نامک (Slug)</label>
                            <input type="text" name="slug" value="<?= htmlspecialchars($category['slug'] ?? '') ?>" required class="ltr-input" placeholder="مثلاً gold-news">
                        </div>
                    </div>

                    <div class="form-group mb-6">
                        <label>توضیحات</label>
                        <textarea name="description" rows="4" placeholder="توضیحات مختصری درباره این دسته بنویسید..."><?= htmlspecialchars($category['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>ترتیب نمایش</label>
                        <input type="number" name="sort_order" value="<?= htmlspecialchars($category['sort_order'] ?? '0') ?>" class="w-32">
                    </div>
                </div>
            </div>

            <!-- SEO Section -->
            <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
                <div class="px-8 py-6 border-b border-slate-100 flex items-center gap-3 bg-slate-50/30">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-slate-400 border border-slate-100">
                        <i data-lucide="search" class="w-5 h-5"></i>
                    </div>
                    <h2 class="text-lg font-black text-slate-800">تنظیمات SEO</h2>
                </div>
                <div class="p-8">
                    <div class="form-group mb-6">
                        <label>عنوان سئو (Meta Title)</label>
                        <input type="text" name="meta_title" value="<?= htmlspecialchars($category['meta_title'] ?? '') ?>" placeholder="عنوان نمایشی در موتورهای جستجو">
                    </div>

                    <div class="form-group mb-6">
                        <label>توضیحات سئو (Meta Description)</label>
                        <textarea name="meta_description" rows="3" placeholder="توضیحات متای این دسته برای گوگل..."><?= htmlspecialchars($category['meta_description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>کلمات کلیدی (Meta Keywords)</label>
                        <input type="text" name="meta_keywords" value="<?= htmlspecialchars($category['meta_keywords'] ?? '') ?>" placeholder="کلمات کلیدی با کاما جدا شوند">
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Column -->
        <div class="lg:col-span-1 space-y-6">
            <div class="glass-card rounded-xl p-6 bg-slate-50/50 sticky top-4 z-10">
                <div class="flex flex-col gap-3">
                    <button type="submit" class="btn-v3 btn-v3-primary w-full h-11 text-sm">
                        <i data-lucide="save" class="w-5 h-5"></i>
                        ذخیره دسته‌بندی
                    </button>
                    <a href="blog_categories.php" class="btn-v3 btn-v3-outline w-full h-11 text-sm">انصراف</a>
                </div>
            </div>
        </div>
    </div>
</form>

<?php include __DIR__ . '/layout/footer.php'; ?>
