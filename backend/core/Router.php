<?php
/**
 * RESTful Router with Parameter Extraction & Middleware
 */

namespace Backend\Core;

use Throwable;

class Router
{
    private array $routes = [];

    public function add(string $method, string $path, callable|array $handler, array $middlewares = []): void
    {
        // Extract parameter names from :param
        $keys = [];
        $pattern = preg_replace_callback('#:([a-zA-Z0-9_]+)#', function ($m) use (&$keys) {
            $keys[] = $m[1];
            return '([^/]+)';
        }, $path);

        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => '#^' . $pattern . '$#',
            'keys' => $keys,
            'handler' => $handler,
            'middlewares' => $middlewares,
            'originalPath' => $path
        ];
    }

    public function get(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->add('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->add('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->add('PUT', $path, $handler, $middlewares);
    }

    public function delete(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->add('DELETE', $path, $handler, $middlewares);
    }

    public function dispatch(Request $request): void
    {
        $requestMethod = $request->getMethod();
        $requestPath = $request->getPath();

        // Handle OPTIONS preflight
        if ($requestMethod === 'OPTIONS') {
            Response::applySecurityHeaders();
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
            http_response_code(204);
            exit;
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $requestMethod) {
                continue;
            }

            if (preg_match($route['pattern'], $requestPath, $matches)) {
                // Extract params
                $params = [];
                foreach ($route['keys'] as $idx => $key) {
                    $params[$key] = urldecode($matches[$idx + 1]);
                }
                $request->setParams($params);

                // Run Middlewares
                $this->runMiddlewares($route['middlewares'], $request);

                // Invoke Handler
                try {
                    $handler = $route['handler'];
                    if (is_array($handler) && count($handler) === 2) {
                        [$class, $method] = $handler;
                        $controller = new $class();
                        $controller->$method($request);
                    } elseif (is_callable($handler)) {
                        $handler($request);
                    }
                    return;
                } catch (Throwable $e) {
                    $u = Auth::user();
                    $uid = $u ? $u['user_id'] : null;

                    // Write detailed error to error log
                    $logDir = __DIR__ . '/../storage';
                    if (!is_dir($logDir)) {
                        @mkdir($logDir, 0755, true);
                    }
                    $logLine = sprintf(
                        "[%s] 500 ERROR: %s in %s:%d\nStack Trace:\n%s\n",
                        date('Y-m-d H:i:s'),
                        $e->getMessage(),
                        $e->getFile(),
                        $e->getLine(),
                        $e->getTraceAsString()
                    );
                    @file_put_contents($logDir . '/app.log', $logLine, FILE_APPEND);

                    // Log audit event
                    AuditLogger::log(
                        $uid,
                        'SYSTEM_EXCEPTION',
                        'server',
                        null,
                        'failed',
                        substr($e->getMessage(), 0, 200)
                    );

                    // Secure Error Handling: Do not leak stack trace or internal server path to users
                    Response::error(500, 'Terjadi kesalahan internal pada server. Silakan hubungi administrator.');
                }
            }
        }

        Response::error(404, "Endpoint {$requestMethod} {$requestPath} tidak ditemukan.");
    }

    private function runMiddlewares(array $middlewares, Request $request): void
    {
        foreach ($middlewares as $mw) {
            if ($mw === 'csrf') {
                if (!CSRF::verify($request)) {
                    Response::error(419, 'Token CSRF tidak valid atau kedaluwarsa. Silakan muat ulang halaman.');
                }
            } elseif ($mw === 'auth') {
                Auth::requireAuth();
            } elseif (str_starts_with($mw, 'role:')) {
                $roles = explode(',', substr($mw, 5));
                Auth::requireRole($roles);
            }
        }
    }
}
