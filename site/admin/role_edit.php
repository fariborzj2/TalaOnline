<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

$id = $_GET['id'] ?? null;
$role = null;
$assigned_permissions = [];

if ($id) {
    check_permission('roles.edit');
    $stmt = $pdo->prepare("SELECT * FROM roles WHERE id = ?");
    $stmt->execute([$id]);
    $role = $stmt->fetch();

    if (!$role) {
        header("Location: roles.php");
        exit;
    }

    // Fetch assigned permissions
    $stmt = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$id]);
    $assigned_permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
} else {
    check_permission('roles.create');
}

// Fetch all permissions grouped by module
$stmt = $pdo->query("SELECT * FROM permissions ORDER BY module ASC, slug ASC");
$all_permissions = [];
while ($row = $stmt->fetch()) {
    $all_permissions[$row['module']][] = $row;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $slug = $_POST['slug'] ?? '';
    $description = $_POST['description'] ?? '';
    $perms = $_POST['perms'] ?? []; // Array of permission IDs

    try {
        $pdo->beginTransaction();

        if ($id) {
            $stmt = $pdo->prepare("UPDATE roles SET name = ?, slug = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $slug, $description, $id]);

            // Update permissions
            $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?")->execute([$id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO roles (name, slug, description) VALUES (?, ?, ?)");
            $stmt->execute([$name, $slug, $description]);
            $id = $pdo->lastInsertId();
        }

        if (!empty($perms)) {
            $stmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($perms as $perm_id) {
                $stmt->execute([$id, $perm_id]);
            }
        }

        $pdo->commit();
        header("Location: roles.php?success=1");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'خطا در ذخیره اطلاعات: ' . $e->getMessage();
    }
}

$page_title = $id ? 'ویرایش نقش' : 'افزودن نقش جدید';
$page_subtitle = 'مدیریت نام و سطوح دسترسی نقش';

include __DIR__ . '/layout/header.php';
?>

<div class="max-w-4xl mx-auto">
    <form method="POST" class="space-y-6">
        <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
            <div class="px-8 py-6 border-b border-slate-100 flex items-center gap-3 bg-slate-50/30">
                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-slate-400 border border-slate-100">
                    <i data-lucide="shield" class="w-5 h-5"></i>
                </div>
                <h2 class="text-lg font-black text-slate-800">اطلاعات کلی نقش</h2>
            </div>

            <?php if ($error): ?>
                <div class="mx-8 mt-6 p-4 bg-rose-50 border border-rose-100 text-rose-600 rounded-lg font-bold text-sm">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <div class="p-8 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label>نام نقش (فارسی)</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($role['name'] ?? '') ?>" required placeholder="مثلاً پشتیبان فنی">
                    </div>
                    <div class="form-group">
                        <label>نامک (Slug - انگلیسی)</label>
                        <input type="text" name="slug" value="<?= htmlspecialchars($role['slug'] ?? '') ?>" required class="ltr text-left" placeholder="technical_support">
                    </div>
                </div>

                <div class="form-group">
                    <label>توضیحات</label>
                    <textarea name="description" rows="2" placeholder="توضیح مختصری درباره وظایف این نقش..."><?= htmlspecialchars($role['description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
            <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/30">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-slate-400 border border-slate-100">
                        <i data-lucide="lock" class="w-5 h-5"></i>
                    </div>
                    <h2 class="text-lg font-black text-slate-800">ماتریس دسترسی‌ها</h2>
                </div>
                <div class="flex items-center gap-4">
                    <button type="button" onclick="selectAll(true)" class="text-[10px] font-black text-indigo-600 hover:underline">انتخاب همه</button>
                    <button type="button" onclick="selectAll(false)" class="text-[10px] font-black text-slate-400 hover:underline">لغو انتخاب</button>
                </div>
            </div>

            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
                    <?php
                    $module_names = [
                        'dashboard' => 'داشبورد',
                        'assets' => 'مدیریت دارایی‌ها',
                        'categories' => 'دسته‌بندی‌های بازار',
                        'platforms' => 'پلتفرم‌ها و صرافی‌ها',
                        'posts' => 'مدیریت وبلاگ',
                        'blog_categories' => 'دسته‌بندی‌های وبلاگ',
                        'blog_tags' => 'برچسب‌ها',
                        'rss' => 'فیدهای خبری RSS',
                        'feedbacks' => 'نظرات و بازخوردها',
                        'settings' => 'تنظیمات سیستم',
                        'users' => 'مدیریت کاربران',
                        'roles' => 'مدیریت نقش‌ها',
                    ];
                    ?>
                    <?php foreach ($all_permissions as $module => $perms): ?>
                    <div class="permission-group">
                        <h3 class="text-sm font-black text-slate-900 mb-4 flex items-center gap-2">
                            <span class="w-1.5 h-4 bg-indigo-600 rounded-full"></span>
                            <?= $module_names[$module] ?? $module ?>
                        </h3>
                        <div class="space-y-3 pr-4 border-r-2 border-slate-50">
                            <?php foreach ($perms as $perm): ?>
                            <label class="flex items-center gap-3 cursor-pointer group mb-0">
                                <div class="relative flex items-center justify-center">
                                    <input type="checkbox" name="perms[]" value="<?= $perm['id'] ?>"
                                           class="peer sr-only"
                                           <?= in_array($perm['id'], $assigned_permissions) ? 'checked' : '' ?>>
                                    <div class="w-5 h-5 border-2 border-slate-200 rounded-md peer-checked:bg-indigo-600 peer-checked:border-indigo-600 transition-all"></div>
                                    <i data-lucide="check" class="w-3 h-3 text-white absolute opacity-0 peer-checked:opacity-100 transition-opacity"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-600 group-hover:text-indigo-600 transition-colors"><?= htmlspecialchars($perm['name']) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flex items-center gap-3 p-8 border-t border-slate-100 bg-slate-50/30">
                <button type="submit" class="btn-v3 btn-v3-primary h-11 px-8 text-sm">
                    <i data-lucide="save" class="w-5 h-5"></i>
                    ذخیره نقش و دسترسی‌ها
                </button>
                <a href="roles.php" class="btn-v3 btn-v3-outline h-11 px-8 text-sm">انصراف</a>
            </div>
        </div>
    </form>
</div>

<script>
function selectAll(state) {
    document.querySelectorAll('input[name="perms[]"]').forEach(cb => cb.checked = state);
}
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
