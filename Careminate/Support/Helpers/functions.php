<?php declare (strict_types = 1);

use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;

// Just include the file at the top of your script
require_once __DIR__ . '/debug_functions.php';

if (! function_exists('value')) {
    function value(mixed $value, ...$args): mixed
    {
        return is_callable($value) ? $value(...$args) : $value;
    }
}



if (!function_exists('request')) {
    /**
     * Get the current Request instance or a specific input value.
     *
     * @param string|array|null $key
     * @param mixed $default
     * @return mixed
     */
    function request(string|array|null $key = null, mixed $default = null): mixed
    {
        static $instance = null;

        if ($instance === null) {
            $instance = Request::createFromGlobals();
        }

        if (is_string($key)) {
            return $instance->input($key, $default);
        }

        if (is_array($key)) {
            return $instance->only($key);
        }

        return $instance;
    }
}

/**
 * Shortcut: Get only specified input keys.
 *
 * @param array|string ...$keys
 * @return array
 */
if (!function_exists('request_only')) {
    function request_only(array|string ...$keys): array
    {
        return request()->only(...$keys);
    }
}

/**
 * Shortcut: Get all input except specified keys.
 *
 * @param array|string ...$keys
 * @return array
 */
if (!function_exists('request_except')) {
    function request_except(array|string ...$keys): array
    {
        return request()->except(...$keys);
    }
}

/**
 * Shortcut: Get all input data (GET + POST + JSON + raw input merged)
 *
 * @return array
 */
if (!function_exists('request_all')) {
    function request_all(): array
    {
        return request()->all();
    }
}

/**
 * Shortcut: Get JSON payload as array.
 *
 * @return array
 */
if (!function_exists('request_json')) {
    function request_json(): array
    {
        return request()->json();
    }
}

/**
 * Shortcut: Check if a key exists in input.
 *
 * @param string $key
 * @return bool
 */
if (!function_exists('request_has')) {
    function request_has(string $key): bool
    {
        return request()->has($key);
    }
}

/**
 * Shortcut: Get a cookie value.
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
if (!function_exists('request_cookie')) {
    function request_cookie(string $key, mixed $default = null): mixed
    {
        return request()->cookie($key, $default);
    }
}

/**
 * Shortcut: Get a header value.
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
if (!function_exists('request_header')) {
    function request_header(string $key, mixed $default = null): mixed
    {
        return request()->header($key) ?? $default;
    }
}

/**
 * Helper function to create a Response instance quickly.
 *
 * Usage:
 *  - response('Hello World', 200)
 *  - response()->json(['name' => 'Caremi'])
 */
if (!function_exists('response')) {
    function response(string|array|null $content = null, int $status = Response::HTTP_OK, array $headers = []): Response
    {
        // If content is an array, automatically create JSON response
        if (is_array($content)) {
            return Response::json($content, $status, $headers);
        }

        // If content is null, return a new empty Response instance for chaining
        if ($content === null) {
            return new Response('', $status, $headers);
        }

        // Otherwise, return a plain text response by default
        return new Response((string) $content, $status, $headers);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $status = Response::HTTP_FOUND, array $headers = []): Response
    {
        return Response::redirect($url, $status, $headers);
    }
}

if (!function_exists('json')) {
    function json(mixed $data, int $status = Response::HTTP_OK, array $headers = []): Response
    {
        return Response::json($data, $status, $headers);
    }
}

