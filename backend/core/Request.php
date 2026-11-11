<?php
/**
 * HTTP Request Representation & Input Parser
 */

namespace Backend\Core;

class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $body;
    private array $headers;
    private array $params = [];

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        
        // Handle method override
        if ($this->method === 'POST') {
            $override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? null;
            if ($override && in_array(strtoupper($override), ['PUT', 'DELETE', 'PATCH'])) {
                $this->method = strtoupper($override);
            }
        }

        $this->parsePath();
        $this->query = $_GET;
        $this->parseBody();
        $this->parseHeaders();
    }

    private function parsePath(): void
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);

        // Normalize Windows backslashes if any
        $path = str_replace('\\', '/', $path);

        // Remove base path if hosted in subfolder (e.g. /Finpro/api/...)
        // Detect script path or project subfolder
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir = str_replace('\\', '/', $scriptDir);
        
        // Remove trailing '/api' from scriptDir if present
        $base = preg_replace('#/api$#', '', $scriptDir);
        if ($base && $base !== '/' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }

        // Ensure leading slash
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        $this->path = $path;
    }

    private function parseBody(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $raw = file_get_contents('php://input');

        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode($raw, true);
            $this->body = is_array($decoded) ? $decoded : [];
        } elseif ($this->method === 'POST') {
            $this->body = $_POST;
        } else {
            // For PUT/PATCH/DELETE with form urlencoded
            parse_str($raw, $parsed);
            $this->body = is_array($parsed) ? $parsed : [];
        }
    }

    private function parseHeaders(): void
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $name = str_replace('_', '-', strtolower($key));
                $headers[$name] = $value;
            }
        }
        $this->headers = $headers;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function query(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function body(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->body;
        }
        return $this->body[$key] ?? $default;
    }

    public function header(string $name, mixed $default = null): mixed
    {
        $name = strtolower($name);
        return $this->headers[$name] ?? $default;
    }

    public function getIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
