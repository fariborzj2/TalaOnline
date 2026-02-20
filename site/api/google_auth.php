<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
session_start();

$client_id = get_setting('google_client_id');
$client_secret = get_setting('google_client_secret');
$enabled = get_setting('google_login_enabled');

if ($enabled !== '1' || empty($client_id) || empty($client_secret)) {
    die("ورود با گوگل فعال نیست یا تنظیم نشده است.");
}

$redirect_uri = get_base_url() . '/api/google_auth.php';

// Step 1: Redirect to Google
if (isset($_GET['action']) && $_GET['action'] === 'login') {
    $params = [
        'client_id' => $client_id,
        'redirect_uri' => $redirect_uri,
        'response_type' => 'code',
        'scope' => 'email profile',
        'access_type' => 'offline',
        'prompt' => 'select_account'
    ];
    $url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query($params);
    header("Location: $url");
    exit;
}

// Step 2: Handle Callback
if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Exchange code for token
    $ch = curl_init("https://oauth2.googleapis.com/token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'code' => $code,
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri' => $redirect_uri,
        'grant_type' => 'authorization_code'
    ]));
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);

    if (isset($data['access_token'])) {
        $access_token = $data['access_token'];

        // Get user info
        $ch = curl_init("https://www.googleapis.com/oauth2/v3/userinfo");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $access_token"]);
        $user_info_raw = curl_exec($ch);
        $user_info = json_decode($user_info_raw, true);
        curl_close($ch);

        if (isset($user_info['email'])) {
            $email = $user_info['email'];
            $name = $user_info['name'];
            $avatar = $user_info['picture'] ?? '';

            // Check if user exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                // Create new user
                $stmt = $pdo->prepare("INSERT INTO users (name, email, avatar, role) VALUES (?, ?, ?, 'user')");
                $stmt->execute([$name, $email, $avatar]);
                $user_id = $pdo->lastInsertId();

                $user = [
                    'id' => $user_id,
                    'name' => $name,
                    'email' => $email,
                    'avatar' => $avatar,
                    'role' => 'user'
                ];
            } else {
                // Update avatar if changed and not already set manually to something else
                if (empty($user['avatar']) && !empty($avatar)) {
                    $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                    $stmt->execute([$avatar, $user['id']]);
                    $user['avatar'] = $avatar;
                }
            }

            // Log user in
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'] ?? 'user';
            $_SESSION['user_avatar'] = $user['avatar'];

            header("Location: /");
            exit;
        }
    }
}

// If something went wrong
header("Location: /?error=google_auth_failed");
exit;
