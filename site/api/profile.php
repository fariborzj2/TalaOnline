<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'لطفا وارد حساب خود شوید.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($action === 'update_info') {
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $phone = $data['phone'] ?? '';

        if (empty($name) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'نام و ایمیل الزامی هستند.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $user_id]);

            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_phone'] = $phone;

            echo json_encode(['success' => true, 'message' => 'اطلاعات با موفقیت بروزرسانی شد.']);
        } catch (PDOException $e) {
            $sqlState = $e->errorInfo[0] ?? $e->getCode();
            if ($sqlState == '23000') {
                echo json_encode(['success' => false, 'message' => 'این ایمیل یا شماره موبایل قبلاً توسط کاربر دیگری ثبت شده است.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'خطایی در بروزرسانی اطلاعات رخ داد.']);
            }
        }
    }
    elseif ($action === 'change_password') {
        $current = $data['current_password'] ?? '';
        $new = $data['new_password'] ?? '';
        $confirm = $data['confirm_password'] ?? '';

        if (empty($current) || empty($new)) {
            echo json_encode(['success' => false, 'message' => 'تمامی فیلدها الزامی هستند.']);
            exit;
        }

        if ($new !== $confirm) {
            echo json_encode(['success' => false, 'message' => 'رمز عبور جدید و تکرار آن مطابقت ندارند.']);
            exit;
        }

        if (strlen($new) < 6) {
            echo json_encode(['success' => false, 'message' => 'رمز عبور جدید باید حداقل ۶ کاراکتر باشد.']);
            exit;
        }

        // Fetch user password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($current, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'رمز عبور فعلی اشتباه است.']);
            exit;
        }

        try {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$hashed, $user_id]);

            echo json_encode(['success' => true, 'message' => 'رمز عبور با موفقیت تغییر کرد.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'خطایی در تغییر رمز عبور رخ داد.']);
        }
    }
}
