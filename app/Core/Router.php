<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $pattern, array $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, array $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $pattern, $handler, $middleware);
    }

    private function addRoute(string $method, string $pattern, array $handler, array $middleware): void
    {
        $this->routes[] = [
            'method'     => strtoupper($method),
            'pattern'    => $pattern,
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $basePath = base_path();

        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath)) ?: '/';
        }

        // Also handle /public prefix if accessed directly
        if (str_starts_with($path, '/public')) {
            $path = substr($path, 7) ?: '/';
        }

        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $regex = $this->compilePattern($route['pattern']);
            if (preg_match($regex, $path, $matches)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }

                // Execute middleware chain
                foreach ($route['middleware'] as $mw) {
                    if (is_string($mw) && class_exists($mw)) {
                        $instance = new $mw();
                        $instance->handle();
                    } elseif (is_callable($mw)) {
                        $mw();
                    }
                }

                [$controllerClass, $action] = $route['handler'];
                $controller = new $controllerClass();
                call_user_func_array([$controller, $action], array_values($params));
                return;
            }
        }

        http_response_code(404);
        Controller::renderError(404, 'Page Not Found', 'The page or shortlink you requested does not exist.');
    }

    private function compilePattern(string $pattern): string
    {
        if ($pattern === '/') {
            return '#^/$#';
        }

        $escaped = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}/',
            static function (array $m): string {
                $name = $m[1];
                $subpattern = $m[2] ?? '[^/]+';
                return '(?P<' . $name . '>' . $subpattern . ')';
            },
            $pattern
        );

        return '#^' . $escaped . '$#';
    }
}

