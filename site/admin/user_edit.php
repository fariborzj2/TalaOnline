<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
check_login();

$id = $_GET['id'] ?? null;
$user = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: users.php");
        exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $password = $_POST['password'] ?? '';

    try {
        if ($id) {
            if ($password) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ?, password = ? WHERE id = ?");
                $stmt->execute([$name, $email, $phone, $role, $hashed_password, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ? WHERE id = ?");
                $stmt->execute([$name, $email, $phone, $role, $id]);
            }
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, role, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $role, $hashed_password]);
        }

        header("Location: users.php?message=success");
        exit;
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'UNIQUE') !== false || strpos($e->getMessage(), '23000') !== false) {
            $error = 'ایمیل یا شماره موبایل قبلاً ثبت شده است.';
        } else {
            $error = 'خطا در ذخیره اطلاعات: ' . $e->getMessage();
        }
    }
}

$page_title = $id ? 'ویرایش کاربر' : 'افزودن کاربر جدید';
$page_subtitle = 'مدیریت اطلاعات و سطح دسترسی کاربر';

include __DIR__ . '/layout/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
        <div class="px-8 py-6 border-b border-slate-100 flex items-center gap-3 bg-slate-50/30">
            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-slate-400 border border-slate-100">
                <i data-lucide="user" class="w-5 h-5"></i>
            </div>
            <h2 class="text-lg font-black text-slate-800">اطلاعات کاربری</h2>
        </div>

        <?php if ($error): ?>
            <div class="mx-8 mt-6 p-4 bg-rose-50 border border-rose-100 text-rose-600 rounded-lg font-bold text-sm">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="p-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label>نام و نام خانوادگی</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required placeholder="مثلاً علی علوی">
                </div>
                <div class="form-group">
                    <label>نقش کاربری</label>
                    <select name="role">
                        <option value="user" <?= ($user['role'] ?? '') === 'user' ? 'selected' : '' ?>>کاربر معمولی</option>
                        <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>مدیر سیستم</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label>ایمیل</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required class="ltr text-left" placeholder="example@mail.com">
                </div>
                <div class="form-group">
                    <label>شماره موبایل</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" class="ltr text-left" placeholder="09123456789">
                </div>
            </div>

            <div class="form-group">
                <label>کلمه عبور <?= $id ? '(خالی بگذارید تا تغییر نکند)' : '' ?></label>
                <input type="password" name="password" <?= $id ? '' : 'required' ?> class="ltr text-left" placeholder="********">
            </div>

            <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
                <button type="submit" class="btn-v3 btn-v3-primary h-11 px-8 text-sm">
                    <i data-lucide="save" class="w-5 h-5"></i>
                    ذخیره کاربر
                </button>
                <a href="users.php" class="btn-v3 btn-v3-outline h-11 px-8 text-sm">انصراف</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
