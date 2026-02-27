<?php

class ErrorHandler {
    private static $isRendering = false;

    public static function register() {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleException($exception) {
        $message = "متاسفانه خطایی در اجرای برنامه رخ داده است.";
        self::renderError(500, 'خطای سیستم', $message, $exception);
    }

    public static function handleError($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            return;
        }

        // For fatal errors or exceptions, we want the beautiful page.
        // For notices/warnings, we might just want to log them, but for this task,
        // if it's being handled here, we might as well show the error page if it's serious.

        if (self::isFatal($errno)) {
            $message = "خطای بحرانی در سیستم رخ داده است.";
            self::renderError(500, 'خطای بحرانی', $message, new ErrorException($errstr, 0, $errno, $errfile, $errline));
        }
    }

    public static function handleShutdown() {
        $error = error_get_last();
        if ($error !== NULL && self::isFatal($error['type'])) {
            self::renderError(500, 'خطای بحرانی', "خطای سیستمی رخ داده است.", new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
        }
    }

    private static function isFatal($type) {
        return in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE, E_RECOVERABLE_ERROR]);
    }

    public static function renderError($code, $title, $message, $exception = null) {
        // Prevent infinite recursion if an error occurs during rendering
        if (self::$isRendering) {
            http_response_code($code);
            die("Critical Error during error rendering: " . $message);
        }
        self::$isRendering = true;

        // Clear any previous output if possible
        if (ob_get_level()) {
            // Check if we can safely clean the buffer
            $status = ob_get_status();
            if ($status && isset($status['flags']) && ($status['flags'] & PHP_OUTPUT_HANDLER_CLEANABLE)) {
                ob_clean();
            }
        }

        if (!headers_sent()) {
            http_response_code($code);
        }

        $details = '';
        // Check for DEV_MODE, default to false if not defined
        $is_dev = defined('DEV_MODE') && DEV_MODE;

        if ($exception) {
            $details = "Exception: " . get_class($exception) . "\n";
            $details .= "Message: " . $exception->getMessage() . "\n";
            $details .= "File: " . $exception->getFile() . " on line " . $exception->getLine() . "\n\n";
            $details .= "Stack Trace:\n" . $exception->getTraceAsString();
        }

        // Detect AJAX or API request
        $is_api = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
                  (strpos($_SERVER['REQUEST_URI'], '/api/') !== false);

        if ($is_api) {
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            echo json_encode([
                'success' => false,
                'code' => $code,
                'error' => $title,
                'message' => $message,
                'details' => $is_dev ? $details : ''
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // For testing/demonstration purposes, if we can't find a config, maybe we can look at the environment.
        // But let's stick to the constant.

        // Ensure LiteSpeed Cache knows not to cache error pages
        if (class_exists('LSCache')) {
            LSCache::sendNoCacheHeaders();
        }

        if (class_exists('View')) {
            try {
                View::renderPage('error', [
                    'code' => $code,
                    'title' => $title,
                    'message' => $message,
                    'details' => $is_dev ? $details : '',
                    'site_title' => $title,
                    'hide_layout_h1' => true
                ]);
            } catch (Exception $e) {
                // Last resort fallback
                self::fallbackRender($code, $title, $message, $is_dev ? $details : '');
            }
        } else {
            self::fallbackRender($code, $title, $message, $is_dev ? $details : '');
        }
        exit;
    }

    private static function fallbackRender($code, $title, $message, $details) {
        echo "<!DOCTYPE html><html lang='fa' dir='rtl'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>$title</title>";
        echo "<style>
            :root { --primary: #249E94; --bg: #F8FAFC; --text: #1E293B; --error: #E11D48; }
            body { font-family: system-ui, -apple-system, sans-serif; background: var(--bg); color: var(--text); display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
            .card { background: white; padding: 40px; border-radius: 24px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); max-width: 500px; width: 100%; text-align: center; border: 1px solid rgba(0,0,0,0.05); }
            .icon { width: 64px; height: 64px; background: #FFF1F2; color: var(--error); border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-size: 32px; font-weight: bold; }
            h1 { font-size: 24px; font-weight: 900; margin: 0 0 12px; color: #0F172A; }
            p { font-size: 16px; line-height: 1.6; margin: 0 0 32px; color: #64748B; font-weight: 500; }
            pre { text-align: left; background: #0F172A; color: #94A3B8; padding: 20px; border-radius: 16px; overflow: auto; max-height: 200px; direction: ltr; font-size: 11px; margin-bottom: 32px; line-height: 1.5; border: 1px solid rgba(255,255,255,0.1); }
            .btn { display: inline-block; background: var(--primary); color: white; padding: 12px 32px; border-radius: 12px; text-decoration: none; font-weight: bold; transition: all 0.2s; box-shadow: 0 10px 15px -3px rgba(36, 158, 148, 0.2); }
            .btn:hover { opacity: 0.9; transform: translateY(-1px); }
        </style></head><body>";
        echo "<div class='card'><div class='icon'>!</div><h1>$title ($code)</h1><p>$message</p>";
        if ($details) echo "<pre>" . htmlspecialchars($details) . "</pre>";
        echo "<a href='/' class='btn'>بازگشت به صفحه اصلی</a></div></body></html>";
    }
}
