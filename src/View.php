<?php

namespace App;

class View
{
    private static $data = [];

    public static function render($template, $data = [], $layout = 'layouts/base')
    {
        self::$data = $data;

        // Render template content
        $ext = str_ends_with($template, '.phtml') ? '' : '.phtml';
        $templatePath = __DIR__ . '/../templates/' . $template . $ext;

        if (!file_exists($templatePath)) {
            throw new \Exception('Template not found: ' . $templatePath);
        }

        ob_start();
        extract($data, EXTR_SKIP);
        require $templatePath;
        $content = ob_get_clean();

        // Render layout if specified
        if ($layout) {
            return self::renderLayout($layout, array_merge($data, ['content' => $content]));
        }

        return $content;
    }

    private static function renderLayout($layout, $data)
    {
        $ext = str_ends_with($layout, '.phtml') ? '' : '.phtml';
        $layoutPath = __DIR__ . '/../templates/' . $layout . $ext;

        if (!file_exists($layoutPath)) {
            return $data['content'];
        }

        ob_start();
        extract($data, EXTR_SKIP);
        require $layoutPath;
        return ob_get_clean();
    }

    public static function partial($partial, $data = [])
    {
        $ext = str_ends_with($partial, '.phtml') ? '' : '.phtml';
        $partialPath = __DIR__ . '/../templates/partials/' . $partial . $ext;

        if (!file_exists($partialPath)) {
            return '';
        }

        ob_start();
        extract($data, EXTR_SKIP);
        require $partialPath;
        return ob_get_clean();
    }

    public static function escape($text)
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
