<?php

/**
 * LiteSpeed Web Cache Manager
 * Handles X-LiteSpeed-Cache-Control and Purge headers
 */
class LSCache {
    /**
     * Initializes the cache headers for the current request
     */
    public static function init() {
        if (self::shouldCache()) {
            self::sendHeaders();
        } else {
            self::sendNoCacheHeaders();
        }
    }

    /**
     * Determines if the current request should be cached
     */
    private static function shouldCache() {
        // 1. Check if enabled in settings
        if (get_setting('lscache_enabled') !== '1') {
            return false;
        }

        // 2. Don't cache for logged in users
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['user_id'])) {
            return false;
        }

        // 3. Don't cache admin pages
        $uri = $_SERVER['REQUEST_URI'];
        if (strpos($uri, '/admin/') !== false || strpos($uri, '/admin') === (strlen($uri) - 6)) {
            return false;
        }

        // 4. Don't cache API requests unless specifically allowed
        if (strpos($uri, '/api/') !== false) {
            return false;
        }

        // 5. Don't cache POST requests or other non-GET/HEAD methods
        if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'HEAD') {
            return false;
        }

        // 6. Don't cache if there is a message in session (like flash messages)
        if (session_status() === PHP_SESSION_ACTIVE && (!empty($_SESSION['message']) || !empty($_SESSION['error']))) {
            return false;
        }

        return true;
    }

    /**
     * Sends LiteSpeed Cache-Control headers
     */
    private static function sendHeaders() {
        $default_ttl = (int)get_setting('lscache_ttl', 3600);
        $ttl = $default_ttl;

        $uri = $_SERVER['REQUEST_URI'];
        $uri_path = explode('?', $uri)[0];

        // Detect Home Page
        $script_name = $_SERVER['SCRIPT_NAME'];
        $base_path = '/';
        if (strpos($script_name, 'index.php') !== false) {
            $base_path = str_replace('index.php', '', $script_name);
        }
        $base_path = rtrim($base_path, '/') . '/';

        if ($uri_path === $base_path || $uri_path === $base_path . 'index.php') {
            $ttl = (int)get_setting('lscache_home_ttl', $default_ttl);
        } elseif (strpos($uri_path, '/blog') !== false) {
            $ttl = (int)get_setting('lscache_blog_ttl', $default_ttl);
        }

        // 1. Remove conflicting headers sent by PHP session_start()
        header_remove('Cache-Control');
        header_remove('Pragma');
        header_remove('Expires');

        // 2. If it's a guest, also remove Set-Cookie to allow LiteSpeed caching
        // Guests don't need a session cookie for viewing static-like pages.
        // If they need a session (e.g. login), it will be started on the next non-cached request (POST).
        if (session_status() === PHP_SESSION_ACTIVE && empty($_SESSION['user_id'])) {
            header_remove('Set-Cookie');
        }

        // 3. Set LiteSpeed specific headers
        header("X-LiteSpeed-Cache-Control: public, max-age=$ttl");

        // 3. Set standard Cache-Control for consistency
        header("Cache-Control: public, max-age=$ttl");

        // 4. Add a tag for easier purging
        header("X-LiteSpeed-Tag: site_content");

        // 5. Remove Vary: Cookie for guest users to allow shared caching
        // If we want different cache for different cookies, we would keep it.
        // But for guest users, the content should be identical.
        header_remove('Vary');
        header("Vary: Accept-Encoding", false);
    }

    /**
     * Sends headers to prevent caching for private/dynamic pages
     */
    private static function sendNoCacheHeaders() {
        if (get_setting('lscache_enabled') === '1') {
            header("X-LiteSpeed-Cache-Control: no-cache");
        }
    }

    /**
     * Triggers a purge of all cached pages
     */
    public static function purgeAll() {
        if (get_setting('lscache_enabled') === '1') {
            if (!headers_sent()) {
                header("X-LiteSpeed-Purge: *");
            } else {
                // If headers are already sent, we might be in the middle of a script
                // LiteSpeed also supports purging via tags in the response
                header("X-LiteSpeed-Purge: site_content", false);
            }
        }
    }

    /**
     * Triggers a purge of specific tags
     */
    public static function purgeTag($tag) {
        if (get_setting('lscache_enabled') === '1' && !headers_sent()) {
            header("X-LiteSpeed-Purge: $tag");
        }
    }

    /**
     * Estimates the size of the LiteSpeed cache
     * Note: This depends on the server configuration.
     */
    public static function getCacheSize() {
        $path = get_setting('lscache_path');

        // Common paths to try if not set
        $paths_to_check = [];
        if ($path) $paths_to_check[] = $path;

        // Try to detect home directory
        $home = getenv('HOME');
        if ($home) {
            $paths_to_check[] = $home . '/lscache';
        }

        // Try web root sibling
        $paths_to_check[] = realpath(__DIR__ . '/../../../') . '/lscache';

        foreach ($paths_to_check as $p) {
            if (is_dir($p) && is_readable($p)) {
                return self::getDirectorySize($p);
            }
        }

        return null;
    }

    /**
     * Calculates directory size recursively
     */
    private static function getDirectorySize($dir) {
        $size = 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }

    /**
     * Formats bytes into human readable string
     */
    public static function formatSize($bytes) {
        if ($bytes === null) return 'نامشخص';
        if ($bytes == 0) return '۰ بایت';

        $units = ['بایت', 'کیلوبایت', 'مگابایت', 'گیگابایت'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        $value = $bytes / pow(1024, $power);
        $formatted = number_format($value, 2);

        // Convert to Persian numbers
        $western = ['0','1','2','3','4','5','6','7','8','9'];
        $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $formatted = str_replace($western, $persian, $formatted);

        return $formatted . ' ' . $units[$power];
    }
}
