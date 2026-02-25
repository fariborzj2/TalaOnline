<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/mail.php';
require_once __DIR__ . '/../../includes/sms.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'لطفا وارد حساب خود شوید.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'get_followers' || $action === 'get_following') {
        $target_username = $_GET['username'] ?? '';
        if (empty($target_username)) {
             echo json_encode(['success' => false, 'message' => 'نام کاربری الزامی است.']);
             exit;
        }

        try {
            // Find user id (Case-insensitive)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?)");
            $stmt->execute([$target_username]);
            $target_user_id = $stmt->fetchColumn();

            if (!$target_user_id) {
                echo json_encode(['success' => false, 'message' => 'کاربر یافت نشد.']);
                exit;
            }

            if ($action === 'get_followers') {
                $sql = "SELECT u.id, u.name, u.username, u.avatar FROM follows f JOIN users u ON f.follower_id = u.id WHERE f.following_id = ? ORDER BY f.created_at DESC";
            } else {
                $sql = "SELECT u.id, u.name, u.username, u.avatar FROM follows f JOIN users u ON f.following_id = u.id WHERE f.follower_id = ? ORDER BY f.created_at DESC";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$target_user_id]);
            $users = $stmt->fetchAll();

            echo json_encode(['success' => true, 'users' => $users]);
        } catch (Exception $e) {
             echo json_encode(['success' => false, 'message' => 'خطایی رخ داد.']);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        echo json_encode(['success' => false, 'message' => 'خطای امنیتی: توکن CSRF معتبر نیست.']);
        exit;
    }

    $input_data = file_get_contents('php://input');
    $data = json_decode($input_data, true);

    if ($action === 'update_avatar') {
        if (!isset($_FILES['avatar'])) {
            echo json_encode(['success' => false, 'message' => 'تصویری انتخاب نشده است.']);
            exit;
        }

        $file = $_FILES['avatar'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'فرمت تصویر غیرمجاز است (فقط JPG, PNG, WEBP).']);
            exit;
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'حجم تصویر نباید بیشتر از ۲ مگابایت باشد.']);
            exit;
        }

        // Use global handle_upload for consistency and WebP conversion
        $avatar_url = handle_upload($file, 'uploads/avatars/');

        if ($avatar_url) {
            $avatar_url = '/' . $avatar_url; // Ensure absolute path
            try {
                $stmt = $pdo->prepare("UPDATE users SET avatar = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$avatar_url, $user_id]);

                $_SESSION['user_avatar'] = $avatar_url;
                echo json_encode(['success' => true, 'message' => 'تصویر پروفایل بروزرسانی شد.', 'avatar' => $avatar_url]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'خطایی در ثبت تصویر رخ داد.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'خطا در آپلود فایل.']);
        }
        exit;
    }

    if ($action === 'update_info') {
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $phone = convert_to_en_num($data['phone'] ?? '');
        $username = strtolower(trim($data['username'] ?? ''));

        if (empty($name) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'نام و ایمیل الزامی هستند.']);
            exit;
        }

        if (!empty($username)) {
            // Validate username format
            if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
                echo json_encode(['success' => false, 'message' => 'نام کاربری باید بین ۳ تا ۳۰ کاراکتر و فقط شامل حروف، اعداد و خط زیر (_) باشد.']);
                exit;
            }

            // Check uniqueness
            if (!is_username_available($username, $user_id)) {
                echo json_encode(['success' => false, 'message' => 'این نام کاربری قبلاً توسط شخص دیگری انتخاب شده است.']);
                exit;
            }
        }

        try {
            // Check if phone or email changed to reset verification
            $stmt = $pdo->prepare("SELECT email, phone, is_verified, is_phone_verified FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $current_user = $stmt->fetch();

            // Normalize and compare
            $new_email = strtolower(trim($email));
            $old_email = strtolower(trim($current_user['email']));
            $new_phone = trim($phone);
            $old_phone = trim($current_user['phone']);

            $email_changed = ($old_email !== $new_email);
            $phone_changed = ($old_phone !== $new_phone);

            $sql = "UPDATE users SET name = ?, email = ?, phone = ?, username = ?, updated_at = CURRENT_TIMESTAMP";
            $params = [$name, $email, $phone, $username];

            if ($email_changed) {
                $sql .= ", is_verified = 0";
                $_SESSION['is_verified'] = 0;
            }
            if ($phone_changed) {
                $sql .= ", is_phone_verified = 0";
                $_SESSION['is_phone_verified'] = 0;
            }

            $sql .= " WHERE id = ?";
            $params[] = $user_id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_phone'] = $phone;
            $_SESSION['user_username'] = $username;

            echo json_encode(['success' => true, 'message' => 'اطلاعات با موفقیت بروزرسانی شد.']);
        } catch (PDOException $e) {
            $sqlState = $e->errorInfo[0] ?? $e->getCode();
            if ($sqlState == '23000') {
                echo json_encode(['success' => false, 'message' => 'این ایمیل، شماره موبایل یا نام کاربری قبلاً توسط کاربر دیگری ثبت شده است.']);
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
    elseif ($action === 'resend_verification') {
        // Fetch user data
        $stmt = $pdo->prepare("SELECT name, email, is_verified FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'کاربر یافت نشد.']);
            exit;
        }

        if ($user['is_verified'] == 1) {
            echo json_encode(['success' => false, 'message' => 'حساب شما قبلاً تایید شده است.']);
            exit;
        }

        // Rate Limiting Check
        $limit_res = check_verification_limit('email', $user['email']);
        if ($limit_res !== true) {
            $wait_mins = ceil($limit_res / 60);
            echo json_encode(['success' => false, 'message' => "تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً $wait_mins دقیقه دیگر دوباره تلاش کنید."]);
            exit;
        }

        try {
            $verification_token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $stmt = $pdo->prepare("UPDATE users SET verification_token = ?, verification_token_expires_at = ? WHERE id = ?");
            $stmt->execute([$verification_token, $expires_at, $user_id]);

            // Send Verification Email
            $base_url = get_site_url();
            $verification_link = $base_url . "/api/verify.php?token=" . $verification_token;

            Mail::queue($user['email'], 'verification', [
                'name' => $user['name'],
                'verification_link' => $verification_link
            ]);

            // Record Attempt
            record_verification_attempt('email', $user['email']);

            echo json_encode(['success' => true, 'message' => 'ایمیل تایید مجدداً برای شما ارسال شد. لطفاً صندوق ورودی و پوشه هرزنامه خود را بررسی کنید.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'خطایی در ارسال ایمیل رخ داد.']);
        }
    }
    elseif ($action === 'send_phone_verification') {
        if (get_setting('mobile_verification_enabled') !== '1') {
            echo json_encode(['success' => false, 'message' => 'تایید شماره موبایل غیرفعال است.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT phone, is_phone_verified FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!$user || empty($user['phone'])) {
            echo json_encode(['success' => false, 'message' => 'شماره موبایل یافت نشد.']);
            exit;
        }

        if ($user['is_phone_verified'] == 1) {
            $_SESSION['is_phone_verified'] = 1;
            echo json_encode(['success' => true, 'message' => 'شماره موبایل شما قبلاً تایید شده است.']);
            exit;
        }

        // Rate Limiting Check
        $limit_res = check_verification_limit('sms', null, $user['phone']);
        if ($limit_res !== true) {
            $wait_mins = ceil($limit_res / 60);
            echo json_encode(['success' => false, 'message' => "تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً $wait_mins دقیقه دیگر دوباره تلاش کنید."]);
            exit;
        }

        $code = random_int(10000, 99999);
        $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        try {
            $stmt = $pdo->prepare("UPDATE users SET phone_verification_code = ?, phone_verification_expires_at = ? WHERE id = ?");
            $stmt->execute([$code, $expires_at, $user_id]);

            $result = SMS::sendLookup($user['phone'], $code);

            if ($result['success']) {
                // Record Attempt
                record_verification_attempt('sms', null, $user['phone']);
            }

            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'خطا در عملیات ارسال پیامک.']);
        }
    }
    elseif ($action === 'verify_phone') {
        $code = convert_to_en_num($data['code'] ?? '');
        if (empty($code)) {
            echo json_encode(['success' => false, 'message' => 'کد تایید الزامی است.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT phone_verification_code, phone_verification_expires_at, is_phone_verified FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'کاربر یافت نشد.']);
            exit;
        }

        if ($user['is_phone_verified'] == 1) {
            $_SESSION['is_phone_verified'] = 1;
            echo json_encode(['success' => true, 'message' => 'شماره موبایل شما قبلاً تایید شده است.']);
            exit;
        }

        if ($user['phone_verification_code'] != $code) {
            echo json_encode(['success' => false, 'message' => 'کد تایید اشتباه است.']);
            exit;
        }

        if (strtotime($user['phone_verification_expires_at']) < time()) {
            echo json_encode(['success' => false, 'message' => 'کد تایید منقضی شده است. لطفا مجددا درخواست دهید.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE users SET is_phone_verified = 1, phone_verification_code = NULL, phone_verification_expires_at = NULL WHERE id = ?");
            $stmt->execute([$user_id]);

            $_SESSION['is_phone_verified'] = 1;
            echo json_encode(['success' => true, 'message' => 'شماره موبایل با موفقیت تایید شد.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'خطایی در تایید شماره موبایل رخ داد.']);
        }
    }
    elseif ($action === 'toggle_follow') {
        $following_id = (int)($data['user_id'] ?? 0);
        if ($following_id <= 0 || $following_id === $user_id) {
            echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر.']);
            exit;
        }

        try {
            // Check if already following
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = ?");
            $stmt->execute([$user_id, $following_id]);
            $is_following = ($stmt->fetchColumn() > 0);

            if ($is_following) {
                // Unfollow
                $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
                $stmt->execute([$user_id, $following_id]);
                $following = false;
            } else {
                // Follow
                $stmt = $pdo->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $following_id]);
                $following = true;
            }

            // Get new count
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
            $stmt->execute([$following_id]);
            $count = $stmt->fetchColumn();

            echo json_encode(['success' => true, 'following' => $following, 'count' => (int)$count]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'خطایی در انجام عملیات رخ داد.']);
        }
        exit;
    }
}
