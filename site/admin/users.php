<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
check_login();

$page_title = 'مدیریت کاربران';
$page_subtitle = 'مشاهده و مدیریت کاربران سیستم';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? 0;

    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: users.php?deleted=1");
        exit;
    }
    elseif ($action === 'toggle_role') {
        // Fetch current role to flip it safely
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $current_role = $stmt->fetchColumn();

        $new_role = $current_role === 'admin' ? 'user' : 'admin';
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $id]);
        header("Location: users.php?updated=1");
        exit;
    }
}

$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

include __DIR__ . '/layout/header.php';
?>

<div class="glass-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>شناسه</th>
                    <th>نام</th>
                    <th>ایمیل</th>
                    <th>موبایل</th>
                    <th>نقش</th>
                    <th>تاریخ عضویت</th>
                    <th class="text-center">عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td class="text-slate-400">#<?= $user['id'] ?></td>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 font-black text-[10px]">
                                <?= mb_substr($user['name'], 0, 1) ?>
                            </div>
                            <span class="font-black text-slate-900"><?= htmlspecialchars($user['name']) ?></span>
                        </div>
                    </td>
                    <td class="ltr text-right"><?= htmlspecialchars($user['email']) ?></td>
                    <td class="ltr text-right"><?= htmlspecialchars($user['phone'] ?? '-') ?></td>
                    <td>
                        <span class="px-2 py-1 rounded text-[10px] font-black <?= $user['role'] === 'admin' ? 'bg-indigo-50 text-indigo-600 border border-indigo-100' : 'bg-slate-50 text-slate-400 border border-slate-100' ?>">
                            <?= $user['role'] === 'admin' ? 'مدیر' : 'کاربر' ?>
                        </span>
                    </td>
                    <td class="text-[11px] text-slate-400"><?= jalali_date('Y/m/d', strtotime($user['created_at'])) ?></td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-2">
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="toggle_role">
                                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                <input type="hidden" name="role" value="<?= $user['role'] ?>">
                                <button type="submit" class="w-8 h-8 bg-white border border-slate-100 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 hover:bg-indigo-50 rounded-lg transition-all flex items-center justify-center group/btn" title="تغییر نقش">
                                    <i data-lucide="shield" class="w-4 h-4 group-hover/btn:scale-110 transition-transform"></i>
                                </button>
                            </form>
                            <form method="POST" class="inline" onsubmit="return confirm('آیا از حذف این کاربر اطمینان دارید؟')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                <button type="submit" class="w-8 h-8 bg-white border border-slate-100 text-slate-400 hover:text-rose-600 hover:border-rose-100 hover:bg-rose-50 rounded-lg transition-all flex items-center justify-center group/btn">
                                    <i data-lucide="trash-2" class="w-4 h-4 group-hover/btn:scale-110 transition-transform"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="6" class="text-center py-10 text-slate-400 font-bold">هیچ کاربری یافت نشد.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
