<?php

require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/View.php';
require_once __DIR__ . '/ErrorHandler.php';
require_once __DIR__ . '/LSCache.php';

if (!defined('DEV_MODE')) {
    // Default to false for security, can be overridden in config.php
    define('DEV_MODE', false);
}

class App {
    private $router;

    public function __construct() {
        ErrorHandler::register();
        $this->router = new Router();
    }

    public function getRouter() {
        return $this->router;
    }

    public function run() {
        ob_start();

        // Load routes
        if (file_exists(__DIR__ . '/../routes.php')) {
            $router = $this->router;
            require_once __DIR__ . '/../routes.php';
        }

        $uri = $_SERVER['REQUEST_URI'];
        // Remove query string for base path comparison
        $uri_path = explode('?', $uri)[0];

        $script_name = $_SERVER['SCRIPT_NAME'];
        // Ensure we handle cases where SCRIPT_NAME might not be index.php (like in some CLI server setups)
        $base_path = '/';
        if (strpos($script_name, 'index.php') !== false) {
            $base_path = str_replace('index.php', '', $script_name);
        }

        if ($base_path !== '/' && strpos($uri_path, $base_path) === 0) {
            $uri = substr($uri, strlen($base_path));
        }
        $uri = '/' . ltrim($uri, '/');

        $this->router->dispatch($uri);

        // Send LiteSpeed headers after all logic is done but before flushing output
        LSCache::init();

        ob_end_flush();
    }
}
