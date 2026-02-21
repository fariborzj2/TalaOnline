<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
check_permission('roles.view');

$page_title = 'مدیریت نقش‌ها';
$page_subtitle = 'تعریف و مدیریت سطوح دسترسی سیستم';
$header_action = has_permission('roles.create') ? '<a href="role_edit.php" class="btn-v3 btn-v3-primary"><i data-lucide="plus" class="w-4 h-4"></i> افزودن نقش جدید</a>' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && has_permission('roles.delete')) {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? 0;

    if ($action === 'delete' && $id > 2) { // Prevent deleting Super Admin and Editor by default
        $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: roles.php?deleted=1");
        exit;
    }
}

$stmt = $pdo->query("SELECT r.*, (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id) as users_count FROM roles r ORDER BY id ASC");
$roles = $stmt->fetchAll();

include __DIR__ . '/layout/header.php';
?>

<div class="glass-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>شناسه</th>
                    <th>نام نقش</th>
                    <th>نامک (Slug)</th>
                    <th>تعداد کاربران</th>
                    <th>توضیحات</th>
                    <th class="text-center">عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $role): ?>
                <tr>
                    <td class="text-slate-400">#<?= $role['id'] ?></td>
                    <td>
                        <span class="font-black text-slate-900"><?= htmlspecialchars($role['name']) ?></span>
                    </td>
                    <td class="ltr text-right"><code class="bg-slate-50 px-2 py-0.5 rounded text-indigo-600 text-[10px]"><?= htmlspecialchars($role['slug']) ?></code></td>
                    <td class="text-center">
                        <span class="bg-indigo-50 text-indigo-600 px-2 py-1 rounded-full text-[10px] font-black">
                            <?= $role['users_count'] ?> کاربر
                        </span>
                    </td>
                    <td class="text-slate-500 whitespace-normal max-w-xs"><?= htmlspecialchars($role['description']) ?></td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-2">
                            <?php if (has_permission('roles.edit')): ?>
                            <a href="role_edit.php?id=<?= $role['id'] ?>" class="w-8 h-8 bg-white border border-slate-100 text-slate-400 hover:text-amber-600 hover:border-amber-100 hover:bg-amber-50 rounded-lg transition-all flex items-center justify-center group/btn" title="ویرایش">
                                <i data-lucide="edit-3" class="w-4 h-4 group-hover/btn:scale-110 transition-transform"></i>
                            </a>
                            <?php endif; ?>

                            <?php if (has_permission('roles.delete') && $role['id'] > 2): ?>
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $role['id'] ?>">
                                <button type="button" onclick="confirmDelete(this.form)" class="w-8 h-8 bg-white border border-slate-100 text-slate-400 hover:text-rose-600 hover:border-rose-100 hover:bg-rose-50 rounded-lg transition-all flex items-center justify-center group/btn">
                                    <i data-lucide="trash-2" class="w-4 h-4 group-hover/btn:scale-110 transition-transform"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
async function confirmDelete(form) {
    if (await showConfirm('آیا از حذف این نقش اطمینان دارید؟ تمام کاربران این نقش به سطح دسترسی عادی برخواهند گشت.')) {
        form.submit();
    }
}
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
