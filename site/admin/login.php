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
    <title>ورود به پنل مدیریت - طلا آنلاین</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #e29b21;
            --bg: #0f172a;
            --card: #1e293b;
            --text: #94a3b8;
            --title: #f1f5f9;
            --border: #334155;
        }
        body {
            font-family: 'Vazirmatn', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-card {
            background: var(--card);
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            border: 1px solid var(--border);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 10px;
        }
        h1 {
            font-size: 1.5rem;
            color: var(--title);
            margin: 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--title);
        }
        input {
            width: 100%;
            padding: 12px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-sizing: border-box;
            font-family: inherit;
            color: var(--title);
        }
        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(226, 155, 33, 0.1);
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        .alert {
            padding: 12px;
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="header">
        <div class="logo">🔐</div>
        <h1>ورود به مدیریت</h1>
    </div>

    <?php if ($error): ?>
        <div class="alert"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>نام کاربری</label>
            <input type="text" name="username" required autocomplete="username">
        </div>
        <div class="form-group">
            <label>رمز عبور</label>
            <input type="password" name="password" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn">ورود به پنل</button>
    </form>

    <div style="text-align: center; margin-top: 20px;">
        <a href="../" style="color: var(--primary); text-decoration: none; font-size: 0.8rem;">← بازگشت به سایت</a>
    </div>
</div>

</body>
</html>
