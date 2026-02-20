<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
session_start();

$action = $_GET['action'] ?? '';

// Ensure phone column exists (Migration)
try {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
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
} catch (Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $hashedPassword]);

            $userId = $pdo->lastInsertId();
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_phone'] = $phone;
            $_SESSION['user_role'] = 'user';

            echo json_encode(['success' => true, 'user' => [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'role' => 'user'
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
            $_SESSION['user_role'] = $user['role'];

            echo json_encode(['success' => true, 'user' => [
                'name' => $user['name'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'role' => $user['role']
            ]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ایمیل یا کلمه عبور اشتباه است.']);
        }
    }
}
elseif ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
}
elseif ($action === 'get_user') {
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'isLoggedIn' => true,
            'user' => [
                'name' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email'],
                'phone' => $_SESSION['user_phone'] ?? '',
                'role' => $_SESSION['user_role'] ?? 'user'
            ]
        ]);
    } else {
        echo json_encode(['isLoggedIn' => false]);
    }
}
