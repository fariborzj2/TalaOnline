<?php

class Router {
    private $routes = [];

    public function add($path, $action) {
        // Convert :param to regex named group
        // Use [^/]+ to support various characters including Unicode for Persian slugs
        $pattern = preg_replace('/\:([a-zA-Z0-9_]+)/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#u';

        $this->routes[$pattern] = $action;
    }

    public function dispatch($uri) {
        // Remove query string
        $uri = explode('?', $uri)[0];
        // Trim trailing slash except for root
        if ($uri != '/') {
            $uri = rtrim($uri, '/');
        }

        foreach ($this->routes as $pattern => $action) {
            if (preg_match($pattern, $uri, $matches)) {
                // Filter out numeric keys from matches (only keep named groups)
                $params = array_filter($matches, function($key) {
                    return !is_numeric($key);
                }, ARRAY_FILTER_USE_KEY);

                return $this->execute($action, $params);
            }
        }

        return $this->notFound();
    }

    private function execute($action, $params) {
        if (is_callable($action)) {
            return call_user_func_array($action, [$params]);
        }

        if (is_string($action)) {
            // Assume it's a page file name
            return View::renderPage($action, $params);
        }

        return $this->notFound();
    }

    private function notFound() {
        http_response_code(404);
        echo "404 Not Found";
        exit;
    }
}
