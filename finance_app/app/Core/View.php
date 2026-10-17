<?php
declare(strict_types=1);

namespace App\Core;

class View
{
    private static string $basePath = '';

    public static function setBasePath(string $path): void
    {
        self::$basePath = rtrim($path, '/');
    }

    public static function getBasePath(): string
    {
        if (empty(self::$basePath)) {
            self::$basePath = dirname(__DIR__) . '/Views';
        }
        return self::$basePath;
    }

    public static function render(string $view, array $data = [], ?string $layout = 'app'): Response
    {
        $viewPath = self::getViewPath($view);
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View file not found: {$view} (looked at {$viewPath})");
        }

        // Extract view data
        extract($data, EXTR_SKIP);

        // Capture view content
        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        // If layout requested, wrap inside layout
        if ($layout !== null) {
            $layoutPath = self::getViewPath('layouts/' . $layout);
            if (!file_exists($layoutPath)) {
                throw new \RuntimeException("Layout file not found: layouts/{$layout} (looked at {$layoutPath})");
            }

            ob_start();
            require $layoutPath;
            $content = ob_get_clean();
        }

        return Response::make($content);
    }

    private static function getViewPath(string $view): string
    {
        return self::getBasePath() . '/' . str_replace('.', '/', $view) . '.php';
    }
}
