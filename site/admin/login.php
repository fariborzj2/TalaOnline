<?php
require_once __DIR__ . '/../../includes/db.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'نام کاربری یا رمز عبور اشتباه است.';
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به مدیریت - طلا آنلاین</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --primary: #e29b21;
            --bg: #0f172a;
            --card: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }
        body {
            font-family: 'Vazirmatn', sans-serif;
            background-color: #f8fafc;
            background-image: radial-gradient(at 0% 0%, rgba(226, 155, 33, 0.05) 0, transparent 50%), radial-gradient(at 50% 0%, rgba(226, 155, 33, 0.05) 0, transparent 50%);
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-card {
            background: var(--card);
            padding: 3rem;
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 440px;
            border: 1px solid var(--border);
            text-align: center;
        }
        .logo-box {
            width: 64px;
            height: 64px;
            background: var(--primary);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 15px -3px rgba(226, 155, 33, 0.3);
        }
        h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }
        p.subtitle {
            color: var(--text-muted);
            margin-bottom: 2.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
            text-align: right;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-main);
            padding-right: 0.5rem;
        }
        .input-wrapper {
            position: relative;
        }
        .input-wrapper i {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            width: 18px;
        }
        input {
            width: 100%;
            padding: 0.875rem 3rem 0.875rem 1rem;
            background: #f8fafc;
            border: 1.5px solid var(--border);
            border-radius: 16px;
            box-sizing: border-box;
            font-family: inherit;
            color: var(--text-main);
            transition: all 0.2s;
            font-size: 1rem;
        }
        input:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(226, 155, 33, 0.1);
        }
        .btn {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 16px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1rem;
            box-shadow: 0 10px 15px -3px rgba(226, 155, 33, 0.2);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 20px -5px rgba(226, 155, 33, 0.3);
        }
        .alert {
            padding: 1rem;
            background: #fee2e2;
            color: #b91c1c;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="logo-box">
        <i data-lucide="lock" style="color: white;"></i>
    </div>
    <h1>خوش آمدید</h1>
    <p class="subtitle">برای دسترسی به مدیریت وارد شوید</p>

    <?php if ($error): ?>
        <div class="alert">
            <i data-lucide="alert-circle" style="width: 18px;"></i>
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>نام کاربری</label>
            <div class="input-wrapper">
                <i data-lucide="user"></i>
                <input type="text" name="username" required autocomplete="username" placeholder="نام کاربری خود را وارد کنید">
            </div>
        </div>
        <div class="form-group">
            <label>رمز عبور</label>
            <div class="input-wrapper">
                <i data-lucide="key-round"></i>
                <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
            </div>
        </div>
        <button type="submit" class="btn">ورود به پنل</button>
    </form>

    <a href="../" class="back-link">
        <i data-lucide="arrow-right" style="width: 16px;"></i>
        بازگشت به سایت
    </a>
</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>
