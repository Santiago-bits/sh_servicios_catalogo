<?php
/**
 * ARCHIVO: core/Router.php
 * ---------------------------------------------------------------------
 * Enrutador con parámetros dinámicos {param}, middlewares y despacho
 * a controladores del namespace App\Controllers.
 */

declare(strict_types=1);

namespace Core;

final class Router
{
    /** @var array<string,array<int,array{pattern:string,regex:string,params:array<int,string>,action:mixed,middleware:array<int,string>,name:?string}>> */
    private array $routes = [
        'GET' => [], 'POST' => [], 'PUT' => [], 'PATCH' => [], 'DELETE' => [],
    ];

    /** @var array<string,string> */
    private array $named = [];

    /** @var array<int,string> */
    private array $groupMiddleware = [];
    private string $groupPrefix = '';

    private mixed $notFoundHandler = null;

    public function get(string $uri, mixed $action, array $middleware = []): self    { return $this->add('GET', $uri, $action, $middleware); }
    public function post(string $uri, mixed $action, array $middleware = []): self   { return $this->add('POST', $uri, $action, $middleware); }
    public function put(string $uri, mixed $action, array $middleware = []): self    { return $this->add('PUT', $uri, $action, $middleware); }
    public function patch(string $uri, mixed $action, array $middleware = []): self  { return $this->add('PATCH', $uri, $action, $middleware); }
    public function delete(string $uri, mixed $action, array $middleware = []): self { return $this->add('DELETE', $uri, $action, $middleware); }

    /** Agrupa rutas bajo un prefijo y/o middlewares comunes. */
    public function group(string $prefix, callable $callback, array $middleware = []): void
    {
        $previousPrefix     = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix     = rtrim($previousPrefix . '/' . trim($prefix, '/'), '/');
        $this->groupMiddleware = array_merge($previousMiddleware, $middleware);

        $callback($this);

        $this->groupPrefix     = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    public function name(string $name): self
    {
        $method = $this->lastMethod;
        if ($method !== null && $this->routes[$method] !== []) {
            $index = array_key_last($this->routes[$method]);
            $this->routes[$method][$index]['name'] = $name;
            $this->named[$name] = $this->routes[$method][$index]['pattern'];
        }
        return $this;
    }

    private ?string $lastMethod = null;

    private function add(string $method, string $uri, mixed $action, array $middleware): self
    {
        $uri = $this->groupPrefix . '/' . trim($uri, '/');
        $uri = '/' . trim($uri, '/');
        $uri = $uri === '//' ? '/' : $uri;

        $params = [];
        $regex  = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)(:([^}]+))?\}/',
            static function (array $m) use (&$params): string {
                $params[] = $m[1];
                $rule     = $m[3] ?? '[^/]+';
                return '(' . $rule . ')';
            },
            $uri
        );

        $this->routes[$method][] = [
            'pattern'    => $uri,
            'regex'      => '#^' . $regex . '$#u',
            'params'     => $params,
            'action'     => $action,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
            'name'       => null,
        ];

        $this->lastMethod = $method;

        return $this;
    }

    public function fallback(mixed $handler): void
    {
        $this->notFoundHandler = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        if (!isset($this->routes[$method])) {
            $this->handleNotFound();
            return;
        }

        foreach ($this->routes[$method] as $route) {
            if (!preg_match($route['regex'], $uri, $matches)) {
                continue;
            }

            array_shift($matches);
            $params = [];
            foreach ($route['params'] as $i => $name) {
                $params[$name] = $matches[$i] ?? null;
            }

            // Middlewares: cada uno puede cortar la ejecución
            foreach ($route['middleware'] as $middleware) {
                $this->runMiddleware($middleware);
            }

            $this->runAction($route['action'], $params);
            return;
        }

        $this->handleNotFound();
    }

    private function runMiddleware(string $definition): void
    {
        [$name, $argument] = array_pad(explode(':', $definition, 2), 2, null);

        $class = 'App\\Middleware\\' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', (string) $name))) . 'Middleware';

        if (!class_exists($class)) {
            throw new \RuntimeException('Middleware inexistente: ' . $class);
        }

        (new $class())->handle($argument);
    }

    /** @param array<string,string|null> $params */
    private function runAction(mixed $action, array $params): void
    {
        if (is_callable($action)) {
            $action(...array_values($params));
            return;
        }

        if (!is_string($action) || !str_contains($action, '@')) {
            throw new \RuntimeException('Definición de ruta inválida.');
        }

        [$controller, $methodName] = explode('@', $action, 2);

        $class = str_starts_with($controller, 'Admin\\')
            ? 'App\\Controllers\\' . $controller
            : 'App\\Controllers\\' . $controller;

        if (!class_exists($class)) {
            throw new \RuntimeException('Controlador inexistente: ' . $class);
        }

        $instance = new $class();

        if (!method_exists($instance, $methodName)) {
            throw new \RuntimeException(sprintf('El método %s::%s no existe.', $class, $methodName));
        }

        $instance->{$methodName}(...array_values($params));
    }

    private function handleNotFound(): void
    {
        http_response_code(404);

        if ($this->notFoundHandler !== null) {
            $this->runAction($this->notFoundHandler, []);
            return;
        }

        echo 'Página no encontrada.';
    }

    /** @param array<string,string|int> $params */
    public function route(string $name, array $params = []): string
    {
        $pattern = $this->named[$name] ?? '/';
        foreach ($params as $key => $value) {
            $pattern = str_replace('{' . $key . '}', (string) $value, $pattern);
        }
        return BASE_URL . $pattern;
    }
}
