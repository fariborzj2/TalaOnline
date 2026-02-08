<?php

class View {
    private static $basePath = __DIR__ . '/../views/';
    private static $layout = 'main';

    public static function setLayout($layout) {
        self::$layout = $layout;
    }

    /**
     * Renders a full page within a layout
     */
    public static function renderPage($page, $data = []) {
        $content = self::renderFile('pages/' . $page, $data);

        if (self::$layout) {
            echo self::renderFile('layouts/' . self::$layout, array_merge($data, ['content' => $content]));
        } else {
            echo $content;
        }
    }

    /**
     * Renders a section
     */
    public static function renderSection($section, $data = []) {
        return self::renderFile('sections/' . $section, $data);
    }

    /**
     * Renders a component (card)
     */
    public static function renderComponent($component, $data = []) {
        return self::renderFile('components/' . $component, $data);
    }

    /**
     * Helper to render a specific file with data extraction
     */
    private static function renderFile($file, $data = []) {
        $path = self::$basePath . $file . '.php';

        if (!file_exists($path)) {
            return "View file not found: $file";
        }

        extract($data);
        ob_start();
        include $path;
        return ob_get_clean();
    }
}
