<?php
declare(strict_types=1);

namespace App\Core;

class Controller
{
    protected function view(string $viewPath, array $data = [], string $layout = 'layouts/app'): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = dirname(__DIR__) . '/Views/' . $viewPath . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException(sprintf('View file not found: %s', $viewPath));
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout !== '') {
            $layoutFile = dirname(__DIR__) . '/Views/' . $layout . '.php';
            if (!file_exists($layoutFile)) {
                throw new \RuntimeException(sprintf('Layout file not found: %s', $layout));
            }
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    protected function redirect(string $path, int $statusCode = 302): never
    {
        $target = str_starts_with($path, 'http://') || str_starts_with($path, 'https://')
            ? $path
            : url($path);

        header('Location: ' . $target, true, $statusCode);
        exit;
    }

    protected function json(array $payload, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function renderError(int $code, string $title, string $message, array $extra = []): never
    {
        http_response_code($code);
        extract($extra, EXTR_SKIP);
        $errorViewMap = [
            403 => 'errors/403',
            404 => 'errors/404',
            410 => $extra['error_view'] ?? 'errors/expired',
            500 => 'errors/500',
        ];
        $viewName = $errorViewMap[$code] ?? 'errors/404';
        $viewFile = dirname(__DIR__) . '/Views/' . $viewName . '.php';

        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo '<h1>' . e((string) $code) . ' - ' . e($title) . '</h1><p>' . e($message) . '</p>';
        }
        exit;
    }
}
