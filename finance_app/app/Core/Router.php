<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, array|callable $handler, array $middleware = []): self
    {
        return $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array|callable $handler, array $middleware = []): self
    {
        return $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, array|callable $handler, array $middleware = []): self
    {
        return $this->add('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, array|callable $handler, array $middleware = []): self
    {
        return $this->add('DELETE', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, array|callable $handler, array $middleware): self
    {
        // Normalize path
        $normalized = '/' . trim($path, '/');
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $normalized,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
        return $this;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->compilePattern($route['path']);
            if (preg_match($pattern, $path, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, fn($k) => !is_numeric($k), ARRAY_FILTER_USE_KEY);

                // Run middleware pipeline
                $middlewareResponse = $this->runMiddleware($route['middleware'], $request);
                if ($middlewareResponse !== null) {
                    return $middlewareResponse;
                }

                // Execute handler
                return $this->executeHandler($route['handler'], $request, $params);
            }
        }

        // 404 Not Found
        if ($request->wantsJson()) {
            return Response::json(['ok' => false, 'error' => 'Route not found'], 404);
        }

        try {
            return View::render('errors.404', ['title' => 'Page Not Found'], 'auth')->status(404);
        } catch (\Throwable $e) {
            return Response::make('<h1>404 Not Found</h1>', 404);
        }
    }

    private function compilePattern(string $path): string
    {
        // Replace {param} with named regex capture groups
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    private function runMiddleware(array $middlewares, Request $request): ?Response
    {
        // Always validate CSRF on POST/PUT/DELETE unless explicitly excluded
        if (in_array($request->method(), ['POST', 'PUT', 'DELETE'], true)) {
            if (!in_array('no_csrf', $middlewares, true)) {
                if (!Csrf::validateRequest($request)) {
                    Logger::warning("CSRF token validation failed for {$request->path()} from {$request->ip()}");
                    if ($request->wantsJson()) {
                        return Response::json(['ok' => false, 'error' => 'Invalid or expired CSRF token.'], 403);
                    }
                    Session::setFlash('error', 'Your session has expired or the form token was invalid. Please try again.');
                    return Response::redirect($request->uri());
                }
            }
        }

        $isInstalled = Config::isInstalled();

        foreach ($middlewares as $mw) {
            switch ($mw) {
                case 'installed':
                    if (!$isInstalled) {
                        return Response::redirect(url('/install'));
                    }
                    break;

                case 'not_installed':
                    if ($isInstalled) {
                        return Response::redirect(url('/'));
                    }
                    break;

                case 'auth':
                    if (!Auth::check()) {
                        if ($request->wantsJson()) {
                            return Response::json(['ok' => false, 'error' => 'Unauthenticated'], 401);
                        }
                        Session::setFlash('warning', 'Please log in to continue.');
                        return Response::redirect(url('/login'));
                    }
                    break;

                case 'guest':
                    if (Auth::check()) {
                        return Response::redirect(url('/dashboard'));
                    }
                    break;

                case 'admin':
                    if (!Auth::isAdmin()) {
                        if ($request->wantsJson()) {
                            return Response::json(['ok' => false, 'error' => 'Unauthorized action'], 403);
                        }
                        Session::setFlash('error', 'Administrator access required.');
                        return Response::redirect(url('/dashboard'));
                    }
                    break;
            }
        }

        return null;
    }

    private function executeHandler(array|callable $handler, Request $request, array $params): Response
    {
        if (is_callable($handler)) {
            $result = call_user_func($handler, $request, ...array_values($params));
        } elseif (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = new $class();
            $result = call_user_func([$controller, $method], $request, ...array_values($params));
        } else {
            throw new \InvalidArgumentException("Invalid route handler specified.");
        }

        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return Response::json($result);
        }

        return Response::make((string)$result);
    }
}
