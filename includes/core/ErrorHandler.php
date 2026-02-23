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
        echo "<!DOCTYPE html><html lang='fa' dir='rtl'><head><meta charset='UTF-8'><title>$title</title>";
        echo "<style>body{font-family:Tahoma,sans-serif;background:#f4f7f6;color:#333;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;}";
        echo ".container{background:white;padding:40px;border-radius:12px;box-shadow:0 4px 6px rgba(0,0,0,0.1);max-width:600px;text-align:center;}";
        echo "h1{color:#c81e1e;margin-top:0;} pre{text-align:left;background:#eee;padding:15px;border-radius:8px;overflow:auto;max-height:300px;direction:ltr;font-size:12px;}</style></head><body>";
        echo "<div class='container'><h1>$code - $title</h1><p>$message</p>";
        if ($details) echo "<pre>$details</pre>";
        echo "<a href='/'>بازگشت به خانه</a></div></body></html>";
    }
}
