<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
check_login();

$id = $_GET['id'] ?? null;
$user = null;

if ($id) {
    check_permission('users.edit');
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: users.php");
        exit;
    }
} else {
    check_permission('users.create');
}

// Fetch all roles for the dropdown
$stmt = $pdo->query("SELECT id, name FROM roles ORDER BY id ASC");
$available_roles = $stmt->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $username = $_POST['username'] ?? '';
    $role_id = $_POST['role_id'] ?? 0;
    $password = $_POST['password'] ?? '';

    if (!empty($username)) {
        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $error = 'نام کاربری نامعتبر است.';
        } elseif (!is_username_available($username, $id)) {
            $error = 'این نام کاربری قبلاً انتخاب شده است.';
        }
    }

    // Fetch role name for backward compatibility in the 'role' column
    $role_name = 'user';
    if ($role_id > 0) {
        $stmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
        $stmt->execute([$role_id]);
        $role_name = $stmt->fetchColumn() ?: 'user';
    }

    try {
        if (!$error) {
            if ($id) {
                if ($password) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, username = ?, role = ?, role_id = ?, password = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $phone, $username, $role_name, $role_id, $hashed_password, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, username = ?, role = ?, role_id = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $phone, $username, $role_name, $role_id, $id]);
                }
            } else {
                if (empty($username)) $username = generate_unique_username($name, $email);
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, username, role, role_id, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $email, $phone, $username, $role_name, $role_id, $hashed_password]);
            }

            header("Location: users.php?message=success");
            exit;
        }
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
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="form-group">
                    <label>نام و نام خانوادگی</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required placeholder="مثلاً علی علوی">
                </div>
                <div class="form-group">
                    <label>نام کاربری (Username)</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" class="ltr text-left" placeholder="username">
                </div>
                <div class="form-group">
                    <label>نقش کاربری (سطح دسترسی)</label>
                    <select name="role_id">
                        <option value="0" <?= ($user['role_id'] ?? 0) == 0 ? 'selected' : '' ?>>کاربر معمولی (بدون دسترسی به پنل)</option>
                        <?php foreach ($available_roles as $role): ?>
                        <option value="<?= $role['id'] ?>" <?= ($user['role_id'] ?? 0) == $role['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($role['name']) ?>
                        </option>
                        <?php endforeach; ?>
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
