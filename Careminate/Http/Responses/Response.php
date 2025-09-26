<?php declare (strict_types = 1);

namespace Careminate\Http\Responses;

use InvalidArgumentException;
use JsonException;

/**
 * HTTP Response class that handles content, status codes, and headers
 */
class Response
{
    /**
     * HTTP status codes
     *
     * @var array<int, string> Map of status codes to reason phrases
     */
    public const HTTP_STATUS_TEXTS = [
        // 1xx Informational
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',

        // 2xx Success
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',

        // 3xx Redirection
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',

        // 4xx Client Error
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',

        // 5xx Server Error
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    // Common HTTP status codes as public constants for convenience
    public const HTTP_CONTINUE              = 100;
    public const HTTP_OK                    = 200;
    public const HTTP_CREATED               = 201;
    public const HTTP_ACCEPTED              = 202;
    public const HTTP_NO_CONTENT            = 204;
    public const HTTP_MOVED_PERMANENTLY     = 301;
    public const HTTP_FOUND                 = 302;
    public const HTTP_SEE_OTHER             = 303;
    public const HTTP_NOT_MODIFIED          = 304;
    public const HTTP_TEMPORARY_REDIRECT    = 307;
    public const HTTP_PERMANENT_REDIRECT    = 308;
    public const HTTP_BAD_REQUEST           = 400;
    public const HTTP_UNAUTHORIZED          = 401;
    public const HTTP_FORBIDDEN             = 403;
    public const HTTP_NOT_FOUND             = 404;
    public const HTTP_METHOD_NOT_ALLOWED    = 405;
    public const HTTP_CONFLICT              = 409;
    public const HTTP_GONE                  = 410;
    public const HTTP_UNPROCESSABLE_ENTITY  = 422;
    public const HTTP_TOO_MANY_REQUESTS     = 429;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;
    public const HTTP_NOT_IMPLEMENTED       = 501;
    public const HTTP_BAD_GATEWAY           = 502;
    public const HTTP_SERVICE_UNAVAILABLE   = 503;
    public const HTTP_GATEWAY_TIMEOUT       = 504;

    /**
     * Default JSON encoding options
     */
    private const DEFAULT_JSON_OPTIONS = JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;

    /**
     * Default encoding for content
     */
    private const DEFAULT_CHARSET = 'UTF-8';

    /**
     * @var string The response content
     */
    private string $content;

    /**
     * @var int HTTP status code
     */
    private int $status;

    /**
     * @var array<string, string> Response headers
     */
    private array $headers = [];

    /**
     * @var bool Whether headers have been sent
     */
    private bool $headersSent = false;

    /**
     * Create a new response
     */
    public function __construct(
        string $content = '',
        int $status = self::HTTP_OK,
        array $headers = []
    ) {
        $this->content = $content;
        $this->setStatus($status);
        $this->setHeaders($headers);
    }

    /**
     * Set a single header
     */
    public function setHeader(string $name, string | int $value): void
    {
        $this->headers[$name] = (string) $value; // cast to string for consistency
    }
    /**
     * Get a header value by name
     */
    public function getHeader(string $name): ?string
    {
        $normalizedName = $this->normalizeHeaderName($name);
        return $this->headers[$normalizedName] ?? null;
    }

    /**
     * Remove a header by name
     */
    public function removeHeader(string $name): static
    {
        $normalizedName = $this->normalizeHeaderName($name);
        unset($this->headers[$normalizedName]);
        return $this;
    }

    /**
     * Merge multiple headers (case-insensitive)
     */
    public function setHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->setHeader((string) $name, (string) $value);
        }
        return $this;
    }

    /**
     * Get all headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set the response content
     */
    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Get the response content
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set the HTTP status code
     *
     * @throws InvalidArgumentException If the status code is invalid
     */
    public function setStatus(int $status): static
    {
        if (! isset(self::HTTP_STATUS_TEXTS[$status])) {
            throw new InvalidArgumentException("Invalid HTTP status code: $status");
        }
        $this->status = $status;
        return $this;
    }

    /**
     * Get the HTTP status code
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Get the status text for the current status code
     */
    public function getStatusText(): string
    {
        return self::HTTP_STATUS_TEXTS[$this->status] ?? '';
    }

    /**
     * Check if the response is successful (2xx status code)
     */
    public function isSuccessful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    /**
     * Check if the response is a redirection (3xx status code)
     */
    public function isRedirection(): bool
    {
        return $this->status >= 300 && $this->status < 400;
    }

    /**
     * Check if the response indicates a client error (4xx status code)
     */
    public function isClientError(): bool
    {
        return $this->status >= 400 && $this->status < 500;
    }

    /**
     * Check if the response indicates a server error (5xx status code)
     */
    public function isServerError(): bool
    {
        return $this->status >= 500 && $this->status < 600;
    }

    /**
     * Send the response
     */
    public function send(): void
    {
        if ($this->headersSent) {
            return;
        }

        ob_start();

        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }

        echo $this->content;

        $this->headersSent = true;

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            ob_end_flush();
        }
    }

    /**
     * Create a JSON response
     *
     * @throws JsonException If encoding fails
     */
    public static function json(
        mixed $data,
        int $status = self::HTTP_OK,
        array $headers = [],
        int $options = self::DEFAULT_JSON_OPTIONS,
        int $depth = 512
    ): static {
        try {
            $content = json_encode($data, $options, $depth);
        } catch (JsonException $e) {
            throw new JsonException('Failed to encode data as JSON: ' . $e->getMessage(), 0, $e);
        }

        return new static(
            $content,
            $status,
            array_merge(['content-type' => 'application/json; charset=' . self::DEFAULT_CHARSET], $headers)
        );
    }

    /**
     * Create an HTML response
     */
    public static function html(string $html, int $status = self::HTTP_OK, array $headers = []): static
    {
        return new static(
            $html,
            $status,
            array_merge(['content-type' => 'text/html; charset=' . self::DEFAULT_CHARSET], $headers)
        );
    }

    /**
     * Create a plain text response
     */
    public static function text(string $text, int $status = self::HTTP_OK, array $headers = []): static
    {
        return new static(
            $text,
            $status,
            array_merge(['content-type' => 'text/plain; charset=' . self::DEFAULT_CHARSET], $headers)
        );
    }

    /**
     * Create a redirect response
     */
    public static function redirect(string $url, int $status = self::HTTP_FOUND, array $headers = []): static
    {
        if (! in_array($status, [self::HTTP_MOVED_PERMANENTLY, self::HTTP_FOUND, self::HTTP_SEE_OTHER,
            self::HTTP_TEMPORARY_REDIRECT, self::HTTP_PERMANENT_REDIRECT], true)) {
            throw new InvalidArgumentException("Invalid redirect status code: $status");
        }

        $headers['location'] = $url;
        return new static('', $status, $headers);
    }

    /**
     * Create a 404 Not Found response
     */
    public static function notFound(string $message = 'Not Found'): static
    {
        return static::text($message, self::HTTP_NOT_FOUND);
    }

    /**
     * Create a 400 Bad Request response
     */
    public static function badRequest(string $message = 'Bad Request'): static
    {
        return static::text($message, self::HTTP_BAD_REQUEST);
    }

    /**
     * Create a 401 Unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized'): static
    {
        return static::text($message, self::HTTP_UNAUTHORIZED);
    }

    /**
     * Create a 403 Forbidden response
     */
    public static function forbidden(string $message = 'Forbidden'): static
    {
        return static::text($message, self::HTTP_FORBIDDEN);
    }

    /**
     * Create a 500 Internal Server Error response
     */
    public static function serverError(string $message = 'Internal Server Error'): static
    {
        return static::text($message, self::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Create a 204 No Content response
     */
    public static function noContent(array $headers = []): static
    {
        return new static('', self::HTTP_NO_CONTENT, $headers);
    }

    /**
     * Send the HTTP headers
     */
    protected function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        // Send the status line
        http_response_code($this->status);

        // Send all headers
        foreach ($this->headers as $name => $value) {
            $headerLine = ucwords($name, '-') . ': ' . $value;
            header($headerLine, true);
        }
    }

    /**
     * Send the response content
     */
    protected function sendContent(): void
    {
        // Don't send content for HEAD requests or 204/304 responses
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'HEAD' ||
            in_array($this->status, [self::HTTP_NO_CONTENT, self::HTTP_NOT_MODIFIED], true)) {
            return;
        }

        echo $this->content;
    }

    /**
     * Normalize a header name
     */
    private function normalizeHeaderName(string $name): string
    {
        return strtolower(trim($name));
    }

    public static function download(string $filePath, ?string $fileName = null): static
    {
        if (! file_exists($filePath)) {
            return new static('File not found', 404);
        }

        $fileName = $fileName ?: basename($filePath);

        $headers = [
            'Content-Description'       => 'File Transfer',
            'Content-Type'              => 'application/octet-stream',
            'Content-Disposition'       => 'attachment; filename="' . $fileName . '"',
            'Content-Transfer-Encoding' => 'binary',
            'Expires'                   => '0',
            'Cache-Control'             => 'must-revalidate',
            'Pragma'                    => 'public',
            'Content-Length'            => (string) filesize($filePath),
        ];

        $content = file_get_contents($filePath);
        if ($content === false) {
            return new static('Error reading file', 500);
        }

        return new static($content, 200, $headers);
    }
}

