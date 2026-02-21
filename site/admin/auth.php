<?php
/**
 * Admin Authentication & Permission Helper
 */
require_once __DIR__ . '/../../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() {
    // Check if user is logged in AND has an administrative role (role_id > 0)
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role_id']) && $_SESSION['user_role_id'] > 0;
}

function check_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Check if current user has a specific permission
 */
function has_permission($permission_slug) {
    global $pdo;
    if (!is_logged_in()) return false;

    // Super Admin (slug: super_admin) - has all permissions
    // We fetch the slug of the role once or use the ID from session if we are sure ID 1 is always super_admin
    if ($_SESSION['user_role_id'] == 1) return true;

    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = ? AND p.slug = ?
        ");
        $stmt->execute([$_SESSION['user_role_id'], $permission_slug]);
        return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Enforce a permission check and redirect/die if denied
 */
function check_permission($permission_slug) {
    check_login();
    if (!has_permission($permission_slug)) {
        http_response_code(403);

        $page_title = 'خطای دسترسی';
        include __DIR__ . '/layout/header.php';
        ?>
        <div class="glass-card p-10 text-center max-w-lg mx-auto my-20">
            <div class="w-20 h-20 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-6">
                <i data-lucide="shield-off" class="w-10 h-10"></i>
            </div>
            <h1 class="text-2xl font-black text-slate-900 mb-2">عدم دسترسی</h1>
            <p class="text-slate-500 font-bold mb-8">شما اجازه دسترسی به این بخش (<?= htmlspecialchars($permission_slug) ?>) را ندارید.</p>
            <a href="index.php" class="btn-v3 btn-v3-primary inline-flex">بازگشت به داشبورد</a>
        </div>
        <?php
        include __DIR__ . '/layout/footer.php';
        exit;
    }
}
