<?php

require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/View.php';

class App {
    private $router;

    public function __construct() {
        $this->router = new Router();
    }

    public function getRouter() {
        return $this->router;
    }

    public function run() {
        // Load routes
        if (file_exists(__DIR__ . '/../routes.php')) {
            $router = $this->router;
            require_once __DIR__ . '/../routes.php';
        }

        $uri = $_SERVER['REQUEST_URI'];
        // Handle subdirectory if any (e.g. /site/index.php)
        // If index.php is used, we might need to adjust URI
        $script_name = $_SERVER['SCRIPT_NAME'];
        $base_path = str_replace('index.php', '', $script_name);
        if (strpos($uri, $base_path) === 0) {
            $uri = substr($uri, strlen($base_path));
        }
        $uri = '/' . ltrim($uri, '/');

        $this->router->dispatch($uri);
    }
}
