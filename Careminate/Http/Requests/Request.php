<?php declare(strict_types=1);

namespace Careminate\Http\Requests;

use Careminate\Support\Arr;
use Careminate\Sessions\SessionInterface;

/**
 * Optimized HTTP Request class
 */
class Request
{
    private const METHODS_WITH_BODY = ['POST', 'PUT', 'PATCH', 'DELETE'];
    private const SPOOFABLE_METHODS = ['PUT', 'PATCH', 'DELETE'];

    private ?array $cachedAll = null;
    private array $normalizedHeaders = [];
/*
    // Optional session & route handling
    private ?SessionInterface $session = null;
    private mixed $routeHandler = null;
    private array $routeHandlerArgs = [];
*/
    public function __construct(
        private readonly array $getParams = [],
        private readonly array $postParams = [],
        private readonly array $cookies = [],
        private readonly array $files = [],
        private readonly array $server = [],
        public readonly array $inputParams = [],
        public readonly string $rawInput = ''
    ) {
        $this->normalizedHeaders = $this->normalizeHeaders($server);
    }

    public static function createFromGlobals(): static
    {
        $requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $rawInput = file_get_contents('php://input') ?: '';
        $inputParams = [];

        if ($rawInput !== '') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (str_contains($contentType, 'json')) {
                $inputParams = json_decode($rawInput, true) ?? [];
            } elseif (!in_array($requestMethod, ['GET', 'POST'], true)) {
                parse_str($rawInput, $inputParams);
            }
        }

        return new static($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER, $inputParams, $rawInput);
    }

    private function normalizeHeaders(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[str_replace('_', '-', substr($key, 5))] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $headers[str_replace('_', '-', $key)] = $value;
            }
        }
        return $headers;
    }

    public function getMethod(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'POST') {
            $spoofed = strtoupper($this->postParams['_method'] ?? $this->header('X-HTTP-Method-Override') ?? '');
            if (in_array($spoofed, self::SPOOFABLE_METHODS, true)) {
                return $spoofed;
            }
        }

        return $method;
    }

    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->getMethod();
    }

    public function getPathInfo(): string
    {
        return rtrim(parse_url($this->server['REQUEST_URI'] ?? '', PHP_URL_PATH), '/') ?: '/';
    }

    
    //  public function getPathInfo(): string
    // {
    //     return strtok($this->server['REQUEST_URI'], '?');
    // }

    public function header(string $name): ?string
    {
        return $this->normalizedHeaders[$name] ?? null;
    }

    public function headers(): array
    {
        return $this->normalizedHeaders;
    }

    public function fullUrl(): string
    {
        $scheme = $this->isSecure() ? 'https' : 'http';
        $host = $this->server['HTTP_HOST'] ?? ($this->server['SERVER_NAME'] ?? 'localhost');

        return sprintf('%s://%s%s', $scheme, $host, $this->server['REQUEST_URI'] ?? '');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->getParams[$key]
            ?? $this->postParams[$key]
            ?? $this->inputParams[$key]
            ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->getParams[$key])
            || isset($this->postParams[$key])
            || isset($this->inputParams[$key]);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->postParams[$key] ?? $this->inputParams[$key] ?? value($default);
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->getParams[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->postParams[$key] ?? $default;
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]['tmp_name'])
            && is_string($this->files[$key]['tmp_name'])
            && $this->files[$key]['tmp_name'] !== ''
            && is_uploaded_file($this->files[$key]['tmp_name']);
    }

    public function allFiles(): array
    {
        return $this->files;
    }

    public function all(): array
    {
        return $this->cachedAll ??= array_merge($this->getParams, $this->postParams, $this->inputParams);
    }

    public function only(array|string ...$keys): array
    {
        return Arr::only($this->all(), $keys);
    }

    public function except(array|string ...$keys): array
    {
        return Arr::except($this->all(), $keys);
    }

    public function json(): array
    {
        $data = json_decode($this->rawInput, true);
        return is_array($data) ? $data : [];
    }

    public function isJson(): bool
    {
        return str_contains((string)$this->header('Content-Type'), 'json');
    }

    public function wantsJson(): bool
    {
        return str_contains((string)$this->header('Accept'), 'json');
    }

    public function isSecure(): bool
    {
        return ($this->server['HTTPS'] ?? '') === 'on'
            || ($this->server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    public function ip(): string
    {
        $ip = $this->server['HTTP_CLIENT_IP']
            ?? $this->server['HTTP_X_FORWARDED_FOR']
            ?? $this->server['REMOTE_ADDR']
            ?? '';

        return explode(',', $ip)[0] ?? '';
    }

    public function userAgent(): ?string
    {
        return $this->header('User-Agent');
    }

    public function getRawInput(): string
    {
        return $this->rawInput;
    }

    
    // Convenience method checks
    public function isPost(): bool { return $this->isMethod('POST'); }
    public function isGet(): bool { return $this->isMethod('GET'); }
    public function isPut(): bool { return $this->isMethod('PUT'); }
    public function isPatch(): bool { return $this->isMethod('PATCH'); }
    public function isDelete(): bool { return $this->isMethod('DELETE'); }
    public function isHead(): bool { return $this->isMethod('HEAD'); }
    public function isOptions(): bool { return $this->isMethod('OPTIONS'); }
/*
    // Session integration
    public function setSession(SessionInterface $session): void { $this->session = $session; }
    public function getSession(): SessionInterface
    {
        if (!$this->session) {
            throw new \RuntimeException("Session not set.");
        }
        return $this->session;
    }
    public function hasSession(): bool { return $this->session !== null; }

    // Route handler support
    public function getRouteHandler(): mixed { return $this->routeHandler; }
    public function setRouteHandler(mixed $handler): void { $this->routeHandler = $handler; }
    public function getRouteHandlerArgs(): array { return $this->routeHandlerArgs; }
    public function setRouteHandlerArgs(array $args): void { $this->routeHandlerArgs = $args; }
    */
}
