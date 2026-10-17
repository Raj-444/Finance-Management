<?php
declare(strict_types=1);

namespace App\Core;

class Request
{
    private string $method;
    private string $uri;
    private string $path;
    private array $queryParams;
    private array $postParams;
    private array $files;
    private ?array $jsonParams = null;
    private array $server;

    public function __construct(
        ?string $method = null,
        ?string $uri = null,
        ?array $queryParams = null,
        ?array $postParams = null,
        ?array $files = null,
        ?array $server = null
    ) {
        $this->server = $server ?? $_SERVER;
        $this->queryParams = $queryParams ?? $_GET;
        $this->postParams = $postParams ?? $_POST;
        $this->files = $files ?? $_FILES;
        
        $rawMethod = strtoupper($method ?? ($this->server['REQUEST_METHOD'] ?? 'GET'));
        if ($rawMethod === 'POST' && isset($this->postParams['_method'])) {
            $rawMethod = strtoupper((string)$this->postParams['_method']);
        }
        $this->method = $rawMethod;

        $this->uri = $uri ?? ($this->server['REQUEST_URI'] ?? '/');
        $parsedUrl = parse_url($this->uri);
        $rawPath = $parsedUrl['path'] ?? '/';

        // Normalize subdirectory script path if present (e.g. if installed in /finance/)
        $scriptName = $this->server['SCRIPT_NAME'] ?? '';
        $baseDir = dirname($scriptName);
        if ($baseDir !== '/' && $baseDir !== '\\' && str_starts_with($rawPath, $baseDir)) {
            $rawPath = substr($rawPath, strlen($baseDir));
        }

        $this->path = '/' . trim($rawPath, '/');
    }

    public static function capture(): self
    {
        return new self();
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->queryParams;
        }
        return $this->queryParams[$key] ?? $default;
    }

    public function post(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->postParams;
        }
        return $this->postParams[$key] ?? $default;
    }

    public function json(?string $key = null, mixed $default = null): mixed
    {
        if ($this->jsonParams === null) {
            $input = file_get_contents('php://input');
            $decoded = json_decode($input, true);
            $this->jsonParams = is_array($decoded) ? $decoded : [];
        }

        if ($key === null) {
            return $this->jsonParams;
        }
        return $this->jsonParams[$key] ?? $default;
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        $all = array_merge($this->queryParams, $this->postParams, $this->json());
        if ($key === null) {
            return $all;
        }
        return $all[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $formatted = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (isset($this->server[$formatted])) {
            return $this->server[$formatted];
        }

        if (isset($this->server['CONTENT_TYPE']) && strcasecmp($name, 'Content-Type') === 0) {
            return $this->server['CONTENT_TYPE'];
        }

        return $default;
    }

    public function ip(): string
    {
        if (!empty($this->server['HTTP_CLIENT_IP'])) {
            return $this->server['HTTP_CLIENT_IP'];
        }
        if (!empty($this->server['HTTP_X_FORWARDED_FOR'])) {
            $list = explode(',', $this->server['HTTP_X_FORWARDED_FOR']);
            return trim($list[0]);
        }
        return $this->server['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function ipBinary(): string
    {
        $ip = $this->ip();
        $binary = @inet_pton($ip);
        if ($binary === false) {
            $binary = inet_pton('127.0.0.1');
        }
        return (string) $binary;
    }

    public function wantsJson(): bool
    {
        $accept = $this->header('Accept', '');
        return str_contains($accept, 'application/json') || $this->isAjax();
    }

    public function isAjax(): bool
    {
        return ($this->header('X-Requested-With') === 'XMLHttpRequest');
    }

    public function isSecure(): bool
    {
        return (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off')
            || (isset($this->server['SERVER_PORT']) && $this->server['SERVER_PORT'] == 443)
            || ($this->header('X-Forwarded-Proto') === 'https');
    }
}
