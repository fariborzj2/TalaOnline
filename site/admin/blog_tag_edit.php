<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
if (isset($_GET["id"])) { check_permission("blog_tags.edit"); } else { check_permission("blog_tags.create"); }

$id = $_GET['id'] ?? null;
$tag = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM blog_tags WHERE id = ?");
    $stmt->execute([$id]);
    $tag = $stmt->fetch();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $name = $_POST['name'] ?? '';
        $slug = $_POST['slug'] ?? '';
        $description = $_POST['description'] ?? '';

        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE blog_tags SET name = ?, slug = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $slug, $description, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO blog_tags (name, slug, description) VALUES (?, ?, ?)");
                $stmt->execute([$name, $slug, $description]);
            }

            header("Location: blog_tags.php?message=success");
            exit;
        } catch (Exception $e) {
            $error = 'خطا در ذخیره اطلاعات: ' . $e->getMessage();
        }
    }
}

$page_title = $id ? 'ویرایش برچسب' : 'افزودن برچسب جدید';
$page_subtitle = 'تنظیمات نام و آدرس برچسب مقالات';

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
                        <i data-lucide="hash" class="w-5 h-5"></i>
                    </div>
                    <h2 class="text-lg font-black text-slate-800">اطلاعات برچسب</h2>
                </div>
                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="form-group">
                            <label>نام برچسب</label>
                            <input type="text" name="name" id="tag-name" value="<?= htmlspecialchars($tag['name'] ?? '') ?>" required placeholder="مثلاً مظنه طلا">
                        </div>
                        <div class="form-group">
                            <label>نامک (Slug)</label>
                            <input type="text" name="slug" id="tag-slug" value="<?= htmlspecialchars($tag['slug'] ?? '') ?>" required class="ltr-input" placeholder="gold-price">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>توضیحات (اختیاری)</label>
                        <textarea name="description" rows="4" placeholder="توضیحات مختصری درباره این برچسب بنویسید..."><?= htmlspecialchars($tag['description'] ?? '') ?></textarea>
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
                        ذخیره برچسب
                    </button>
                    <a href="blog_tags.php" class="btn-v3 btn-v3-outline w-full h-11 text-sm">انصراف</a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    const nameInput = document.getElementById('tag-name');
    const slugInput = document.getElementById('tag-slug');

    if (nameInput && slugInput && !slugInput.value) {
        nameInput.addEventListener('input', () => {
            const slug = nameInput.value
                .toLowerCase()
                .replace(/[^\u0600-\u06FFa-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
            slugInput.value = slug;
        });
    }
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
