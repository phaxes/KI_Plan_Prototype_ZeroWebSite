<?php

namespace App;

class Router
{
    private array $routes = [];
    private string $currentMethod;
    private string $currentPath;

    public function __construct()
    {
        $this->currentMethod = $_SERVER['REQUEST_METHOD'];
        $this->currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        // Remove trailing slash except for root
        if ($this->currentPath !== '/' && str_ends_with($this->currentPath, '/')) {
            $this->currentPath = rtrim($this->currentPath, '/');
        }
    }

    public function get(string $path, callable|string $handler): self
    {
        return $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|string $handler): self
    {
        return $this->add('POST', $path, $handler);
    }

    public function put(string $path, callable|string $handler): self
    {
        return $this->add('PUT', $path, $handler);
    }

    public function delete(string $path, callable|string $handler): self
    {
        return $this->add('DELETE', $path, $handler);
    }

    private function add(string $method, string $path, callable|string $handler): self
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
        return $this;
    }

    public function dispatch(): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $this->currentMethod) continue;

            if ($this->pathMatches($route['path'], $params)) {
                $this->handle($route['handler'], $params);
                return;
            }
        }

        // 404
        http_response_code(404);
        require __DIR__ . '/../templates/errors/404.php';
    }

    private function pathMatches(string $pattern, &$params = []): bool
    {
        $params = [];
        $patternParts = array_filter(explode('/', $pattern));
        $pathParts = array_filter(explode('/', $this->currentPath));

        if (count($patternParts) !== count($pathParts)) {
            return false;
        }

        foreach ($patternParts as $i => $part) {
            if (str_starts_with($part, '{') && str_ends_with($part, '}')) {
                $paramName = trim($part, '{}');
                $params[$paramName] = $pathParts[$i];
            } elseif ($part !== $pathParts[$i]) {
                return false;
            }
        }

        return true;
    }

    private function handle(callable|string $handler, array $params): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, [$params, $_POST, $_GET]);
        } elseif (is_string($handler)) {
            [$controllerClass, $method] = explode('@', $handler);
            $controllerFqn = "App\\Controllers\\$controllerClass";
            $controller = new $controllerFqn();
            call_user_func_array([$controller, $method], [$params, $_POST, $_GET]);
        }
    }
}
