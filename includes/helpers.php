<?php

/**
 * Converts Persian and Arabic numerals to English (Western) numerals
 */
function convert_to_en_num($str) {
    if ($str === null || $str === '') return $str;

    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    $str = str_replace($persian, $western, $str);
    $str = str_replace($arabic, $western, $str);

    return $str;
}

function fa_num($num) {
    if ($num === null || $num === '') return '---';

    // Simple fallback/digit replacement is safer for formatted strings like dates
    $western = ['0','1','2','3','4','5','6','7','8','9'];
    $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];

    if (is_numeric($num) && class_exists('NumberFormatter')) {
        $fmt = new NumberFormatter('fa_IR', NumberFormatter::DECIMAL);
        return $fmt->format((float)$num);
    }

    return str_replace($western, $persian, (string)$num);
}

function fa_price($num) {
    return fa_num($num);
}

function jalali_date($date = 'now', $format = 'long', $as_tag = false) {
    // Standardize 'now' and empty values
    if (!$date || trim($date) === '' || $date === 'now') {
        $date = 'now';
    }

    // Handle invalid or "zero" dates explicitly
    if ($date === '0000-00-00 00:00:00' || $date === '0000-00-00' || str_starts_with($date, '0000')) {
        return '---';
    }

    // Ensure we have a valid DateTime object in Tehran timezone
    $tz = new DateTimeZone('Asia/Tehran');
    try {
        if ($date === 'now') {
            $dt = new DateTime('now', $tz);
        } elseif (is_numeric($date)) {
            $dt = new DateTime('@' . $date);
            $dt->setTimezone($tz);
        } else {
            // Clean and parse the date string
            $dt = new DateTime((string)$date, $tz);

            // If the Gregorian year is lower than 1000, it's almost certainly an invalid/zero date overflow
            if ((int)$dt->format('Y') < 1000) {
                return '---';
            }
        }
    } catch (Exception $e) {
        // Fallback for parsing errors: only show current time if requested 'now'
        if ($date === 'now') {
             try { $dt = new DateTime('now', $tz); } catch(Exception $ex) { return '---'; }
        } else {
             return '---';
        }
    }

    $timestamp = $dt->getTimestamp();

    if (!class_exists('IntlDateFormatter')) {
        // Very basic fallback if Intl is missing
        $human_date = fa_num($dt->format('Y/m/d'));
    } else {
        // Standard formats
        $pattern = 'd MMMM y'; // Default: 28 Bahman 1404

        if ($format === 'weekday') {
            $pattern = 'EEEE d MMMM y'; // Tuesday 28 Bahman 1404
        } elseif ($format === 'time') {
            $pattern = 'd MMMM y | HH:mm';
        } elseif ($format === 'full') {
            $pattern = 'EEEE d MMMM y ساعت HH:mm';
        } elseif ($format === 'compact') {
            $pattern = 'yyyy/MM/dd';
        } elseif ($format === 'day_month') {
            $pattern = 'd MMMM';
        }

        $fmt = new IntlDateFormatter(
            'fa_IR@calendar=persian',
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            'Asia/Tehran',
            IntlDateFormatter::TRADITIONAL,
            $pattern
        );

        $result = $fmt->format($timestamp);

        // Some Intl implementations might return western digits even for fa_IR
        $human_date = fa_num($result);
    }

    if ($as_tag) {
        return '<time datetime="' . $dt->format('Y-m-d\TH:i:sP') . '">' . $human_date . '</time>';
    }

    return $human_date;
}

/**
 * Returns the Jalali date wrapped in a <time> tag
 */
function jalali_time_tag($date = 'now', $format = 'long') {
    return jalali_date($date, $format, true);
}

function get_trend_arrow($change) {
    if ($change > 0) return '<span class="trend-arrow trend-up"></span>';
    if ($change < 0) return '<span class="trend-arrow trend-down"></span>';
    return '';
}

function get_base_url() {
    $protocol = (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') ? 'http' : 'https';
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';

    $base_path = '';
    if (isset($_SERVER['SCRIPT_NAME'])) {
        $base_path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        // Strip common subdirectories to get the site root
        $base_path = preg_replace('/(\/api|\/admin|\/includes)$/', '', $base_path);
        if ($base_path === '/' || $base_path === '\\') {
            $base_path = '';
        }
    }

    return rtrim("$protocol://$host$base_path", '/');
}

/**
 * Returns the configured site URL or falls back to the current base URL
 */
function get_site_url() {
    $url = get_setting('site_url');
    return !empty($url) ? rtrim($url, '/') : get_base_url();
}

function get_current_url() {
    return get_base_url() . $_SERVER['REQUEST_URI'];
}

/**
 * Returns a versioned URL for a static asset to prevent caching issues
 */
function versioned_asset($path) {
    if (empty($path)) return '';
    if (str_starts_with($path, 'http')) return $path;

    $clean_path = ltrim($path, '/');
    $full_path = __DIR__ . '/../site/' . $clean_path;

    if (file_exists($full_path)) {
        $version = filemtime($full_path);
        return '/' . $clean_path . '?v=' . $version;
    }

    return '/' . $clean_path;
}

/**
 * Returns optimized asset URL (prefers WebP) with cache busting
 */
function get_asset_url($path) {
    if (empty($path)) return versioned_asset('/assets/images/gold/gold.webp');

    if (str_starts_with($path, 'http')) return $path;

    $clean_path = ltrim($path, '/');
    $ext = pathinfo($clean_path, PATHINFO_EXTENSION);

    $target_path = '/' . $clean_path;

    if (strtolower($ext) !== 'webp') {
        // Check if webp exists
        $webp_path = preg_replace('/\.(png|jpg|jpeg)$/i', '.webp', $clean_path);
        if (file_exists(__DIR__ . '/../site/' . $webp_path)) {
            $target_path = '/' . $webp_path;
        }
    }

    return versioned_asset($target_path);
}

/**
 * Ensures the session is started if it hasn't been already.
 */
function ensure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        // If LiteSpeed Cache is enabled, we want to control headers ourselves
        if (get_setting('lscache_enabled') === '1') {
            session_cache_limiter('');
        }
        session_start();
    }
}

/**
 * Generates and returns a CSRF token
 */
function csrf_token() {
    ensure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies the CSRF token
 */
function verify_csrf_token($token) {
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Generates a unique username for a user
 */
function generate_unique_username($name, $email = '') {
    global $pdo;
    if (!$pdo) return uniqid('user_');

    // Clean name: remove non-alphanumeric, lowercase
    $base = preg_replace('/[^a-zA-Z0-9]/', '', $name);
    if (empty($base) && !empty($email)) {
        $base = explode('@', $email)[0];
        $base = preg_replace('/[^a-zA-Z0-9]/', '', $base);
    }

    if (empty($base)) {
        $base = 'user';
    }

    $base = strtolower($base);
    $username = $base;
    $counter = 1;

    // Check if initial base is available
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetchColumn() == 0) {
        return $username;
    }

    // Try adding numbers until unique
    while (true) {
        $username = $base . random_int(100, 999);
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() == 0) {
            return $username;
        }
        if ($counter > 20) { // Safety break
            return $base . uniqid();
        }
        $counter++;
    }
}

/**
 * Checks if a username is available (not taken by another user)
 */
function is_username_available($username, $exclude_user_id = null) {
    global $pdo;
    if (!$pdo) return false;

    $sql = "SELECT COUNT(*) FROM users WHERE username = ?";
    $params = [$username];

    if ($exclude_user_id) {
        $sql .= " AND id != ?";
        $params[] = $exclude_user_id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() == 0;
}

/**
 * Checks if an action is rate limited
 * @return true|int Returns true if not limited, or number of seconds to wait if limited
 */
function check_rate_limit($action_type, $identifier_type, $identifier_value, $limit_prefix = null) {
    global $pdo;
    if (!$pdo) return true;

    if ($limit_prefix === null) {
        $limit_prefix = $action_type;
    }

    // A. Check for active lock
    $stmt = $pdo->prepare("SELECT unlock_at FROM verification_locks WHERE identifier_type = ? AND identifier_value = ? AND unlock_at > ? ORDER BY unlock_at DESC LIMIT 1");
    $stmt->execute([$identifier_type, $identifier_value, date('Y-m-d H:i:s')]);
    $lock = $stmt->fetch();
    if ($lock) {
        $wait = strtotime($lock['unlock_at']) - time();
        return ($wait > 0) ? $wait : true;
    }

    // B. Check counts in window
    $max = (int)get_setting("rate_limit_{$limit_prefix}_max", 5);
    $window = (int)get_setting("rate_limit_{$limit_prefix}_window", 15);

    // Format for MySQL/SQLite compatibility
    $since = date('Y-m-d H:i:s', strtotime("-$window minutes"));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM verification_attempts WHERE type = ? AND identifier_type = ? AND identifier_value = ? AND created_at > ?");
    $stmt->execute([$action_type, $identifier_type, $identifier_value, $since]);
    $count = $stmt->fetchColumn();

    if ($count >= $max) {
        // Create lock
        $lock_duration = (int)get_setting("rate_limit_{$limit_prefix}_lock", 60);

        // Progressive lock logic
        if (get_setting('rate_limit_progressive') === '1') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM verification_locks WHERE identifier_type = ? AND identifier_value = ? AND created_at > ?");
            $stmt->execute([$identifier_type, $identifier_value, date('Y-m-d H:i:s', strtotime("-24 hours"))]);
            $lock_count = $stmt->fetchColumn();
            if ($lock_count > 0) {
                $lock_duration *= pow(2, min($lock_count, 10)); // Cap at 2^10 to avoid overflow
            }
        }

        $unlock_at = date('Y-m-d H:i:s', strtotime("+$lock_duration minutes"));
        $stmt = $pdo->prepare("INSERT INTO verification_locks (identifier_type, identifier_value, unlock_at) VALUES (?, ?, ?)");
        $stmt->execute([$identifier_type, $identifier_value, $unlock_at]);

        return $lock_duration * 60;
    }

    return true;
}

/**
 * Checks if a verification action is rate limited
 * @return true|int Returns true if not limited, or number of seconds to wait if limited
 */
function check_verification_limit($action_type, $email = null, $phone = null) {
    global $pdo;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // 1. Check IP Limit
    $ip_res = check_single_verification_limit('ip', $ip, 'ip');
    if ($ip_res !== true) return $ip_res;

    // 2. Check Identifier Limit
    if ($action_type === 'sms' && $phone) {
        $sms_res = check_single_verification_limit('phone', $phone, 'sms');
        if ($sms_res !== true) return $sms_res;
    } elseif ($action_type === 'email' && $email) {
        $email_res = check_single_verification_limit('email', $email, 'email');
        if ($email_res !== true) return $email_res;
    }

    return true;
}

/**
 * Internal helper to check a single identifier against its limit
 */
function check_single_verification_limit($identifier_type, $value, $limit_prefix) {
    global $pdo;
    if (!$pdo) return true;

    // A. Check for active lock
    $stmt = $pdo->prepare("SELECT unlock_at FROM verification_locks WHERE identifier_type = ? AND identifier_value = ? AND unlock_at > ? ORDER BY unlock_at DESC LIMIT 1");
    $stmt->execute([$identifier_type, $value, date('Y-m-d H:i:s')]);
    $lock = $stmt->fetch();
    if ($lock) {
        $wait = strtotime($lock['unlock_at']) - time();
        return ($wait > 0) ? $wait : true;
    }

    // B. Check counts in window
    $max = (int)get_setting("rate_limit_{$limit_prefix}_max", 5);
    $window = (int)get_setting("rate_limit_{$limit_prefix}_window", 15);

    // Format for MySQL/SQLite compatibility
    $since = date('Y-m-d H:i:s', strtotime("-$window minutes"));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM verification_attempts WHERE identifier_type = ? AND identifier_value = ? AND created_at > ?");
    $stmt->execute([$identifier_type, $value, $since]);
    $count = $stmt->fetchColumn();

    if ($count >= $max) {
        // Create lock
        $lock_duration = (int)get_setting("rate_limit_{$limit_prefix}_lock", 60);

        // Progressive lock logic
        if (get_setting('rate_limit_progressive') === '1') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM verification_locks WHERE identifier_type = ? AND identifier_value = ? AND created_at > ?");
            $stmt->execute([$identifier_type, $value, date('Y-m-d H:i:s', strtotime("-24 hours"))]);
            $lock_count = $stmt->fetchColumn();
            if ($lock_count > 0) {
                $lock_duration *= pow(2, min($lock_count, 10)); // Cap at 2^10 to avoid overflow
            }
        }

        $unlock_at = date('Y-m-d H:i:s', strtotime("+$lock_duration minutes"));
        $stmt = $pdo->prepare("INSERT INTO verification_locks (identifier_type, identifier_value, unlock_at) VALUES (?, ?, ?)");
        $stmt->execute([$identifier_type, $value, $unlock_at]);

        return $lock_duration * 60;
    }

    return true;
}

/**
 * Records an attempt for rate limiting
 */
function record_rate_limit_attempt($action_type, $identifier_type, $identifier_value) {
    global $pdo;
    if (!$pdo) return;

    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("INSERT INTO verification_attempts (type, identifier_type, identifier_value, created_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$action_type, $identifier_type, $identifier_value, $now]);
}

/**
 * Records a verification attempt for rate limiting
 */
function record_verification_attempt($action_type, $email = null, $phone = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // Record for IP
    record_rate_limit_attempt($action_type, 'ip', $ip);

    // Record for Email
    if ($email) {
        record_rate_limit_attempt($action_type, 'email', $email);
    }

    // Record for Phone
    if ($phone) {
        record_rate_limit_attempt($action_type, 'phone', $phone);
    }
}
