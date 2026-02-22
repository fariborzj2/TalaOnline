<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/mail.php';
session_start();

$action = $_GET['action'] ?? '';

if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

// Ensure users table and columns exist (Migration)
try {
    if (!$pdo) throw new Exception("Database connection not available");
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `name` VARCHAR(255),
            `email` VARCHAR(255) UNIQUE,
            `phone` VARCHAR(20) UNIQUE,
            `username` VARCHAR(50) UNIQUE,
            `password` VARCHAR(255),
            `avatar` VARCHAR(255),
            `role` VARCHAR(20) DEFAULT 'user',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255),
            `email` VARCHAR(255) UNIQUE,
            `phone` VARCHAR(20) UNIQUE,
            `username` VARCHAR(50) UNIQUE,
            `password` VARCHAR(255),
            `avatar` VARCHAR(255),
            `role` VARCHAR(20) DEFAULT 'user',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    // Check for missing columns in case of old table
    $cols = [];
    if ($driver === 'sqlite') {
        $stmt = $pdo->query("PRAGMA table_info(users)");
        while ($row = $stmt->fetch()) { $cols[] = $row['name']; }
    } else {
        $cols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    }
    if (!in_array('phone', $cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20)");
    }
    if (!in_array('avatar', $cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255)");
    }
    if (!in_array('username', $cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN username VARCHAR(50)");
        try {
            $pdo->exec("CREATE UNIQUE INDEX idx_users_username ON users(username)");
        } catch (Exception $e) {}
    }
} catch (Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        echo json_encode(['success' => false, 'message' => 'خطای امنیتی: توکن CSRF معتبر نیست.']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if ($action === 'register') {
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $phone = $data['phone'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($name) || empty($email) || empty($phone) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'تمامی فیلدها الزامی هستند.']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'ایمیل نامعتبر است.']);
            exit;
        }

        try {
            $username = generate_unique_username($name, $email);
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $verification_token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, username, password, is_verified, verification_token, verification_token_expires_at) VALUES (?, ?, ?, ?, ?, 0, ?, ?)");
            $stmt->execute([$name, $email, $phone, $username, $hashedPassword, $verification_token, $expires_at]);

            $userId = $pdo->lastInsertId();

            // Send Verification Email
            $verification_link = get_base_url() . "/api/verify.php?token=" . $verification_token;
            Mail::send($email, 'verification', [
                'name' => $name,
                'verification_link' => $verification_link
            ]);

            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_username'] = $username;
            $_SESSION['user_phone'] = $phone;
            $_SESSION['user_role'] = 'user';
            $_SESSION['user_role_id'] = 0;
            $_SESSION['user_avatar'] = '';
            $_SESSION['is_verified'] = 0;

            echo json_encode(['success' => true, 'message' => 'ثبت‌نام با موفقیت انجام شد. لطفاً ایمیل خود را جهت تایید حساب بررسی کنید.', 'user' => [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'username' => $username,
                'role' => 'user',
                'role_id' => 0,
                'avatar' => '',
                'is_verified' => 0
            ]]);
        } catch (PDOException $e) {
            $sqlState = $e->errorInfo[0] ?? $e->getCode();
            if ($sqlState == '23000') {
                echo json_encode(['success' => false, 'message' => 'این ایمیل یا شماره موبایل قبلاً ثبت شده است.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'خطایی در ثبت نام رخ داد.']);
            }
        }
    }
    elseif ($action === 'login') {
        $identifier = $data['email'] ?? ''; // This can be email or phone
        $password = $data['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'ایمیل/موبایل و کلمه عبور الزامی هستند.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_phone'] = $user['phone'];
            $_SESSION['user_username'] = $user['username'] ?? '';
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_role_id'] = $user['role_id'] ?? 0;
            $_SESSION['user_avatar'] = $user['avatar'] ?? '';
            $_SESSION['is_verified'] = $user['is_verified'] ?? 0;

            echo json_encode(['success' => true, 'user' => [
                'name' => $user['name'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'username' => $user['username'] ?? '',
                'role' => $user['role'],
                'role_id' => $user['role_id'] ?? 0,
                'avatar' => $user['avatar'] ?? '',
                'is_verified' => $user['is_verified'] ?? 0
            ]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ایمیل یا کلمه عبور اشتباه است.']);
        }
    }
}
elseif ($action === 'get_user') {
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'isLoggedIn' => true,
            'user' => [
                'name' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email'],
                'phone' => $_SESSION['user_phone'] ?? '',
                'username' => $_SESSION['user_username'] ?? '',
                'role' => $_SESSION['user_role'] ?? 'user',
                'avatar' => $_SESSION['user_avatar'] ?? '',
                'is_verified' => $_SESSION['is_verified'] ?? 0
            ]
        ]);
    } else {
        echo json_encode(['isLoggedIn' => false]);
    }
}
