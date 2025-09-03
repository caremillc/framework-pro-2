<?php declare (strict_types = 1);

use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;

// Just include the file at the top of your script
require_once __DIR__ . '/debug_functions.php';

/**
 * Dump and die (debugging helper).
 */
if (!function_exists('dd')) {
    function dd(...$vars): void
    {
        foreach ($vars as $v) {
            echo '<pre>';
            var_dump($v);
            echo '</pre>';
        }
        die(1);
    }
}

/**
 * End Response Helper Function
 */

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
 *  Start Response Helper functions
 */


/**
 * Return a new Response instance with given content.
 */
if (!function_exists('response')) {
    function response(string $content = '', int $status = 200, array $headers = []): Response
    {
        return new Response(content: $content, status: $status, headers: $headers);
    }
}

/**
 * Return a JSON response.
 */
if (!function_exists('json')) {
    function json(array|object $data = [], int $status = 200, array $headers = []): Response
    {
        $headers = array_merge(['Content-Type' => 'application/json'], $headers);
        return new Response(content: json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), status: $status, headers: $headers);
    }
}

/**
 * Return a redirect response.
 */
if (!function_exists('redirect')) {
    function redirect(string $url, int $status = 302, array $headers = []): Response
    {
        $headers = array_merge(['Location' => $url], $headers);
        return new Response(content: '', status: $status, headers: $headers);
    }
}

/**
 * Return a plain text response.
 */
if (!function_exists('text')) {
    function text(string $content, int $status = 200, array $headers = []): Response
    {
        $headers = array_merge(['Content-Type' => 'text/plain; charset=utf-8'], $headers);
        return new Response(content: $content, status: $status, headers: $headers);
    }
}



/**
 * start paths
 */
if (!function_exists('base_path')) {
    function base_path(?string $file = null)
    {
        return  ROOT_DIR . '/../' . $file;
    }
}

if (!function_exists('config')) {
    function config(?string $file = null)
    {
        $seprate = explode('.', $file);
        if ((!empty($seprate) && count($seprate) > 1) && !is_null($file)) {
            $file = include base_path('config/') . $seprate[0] . '.php';
            return isset($file[$seprate[1]]) ? $file[$seprate[1]] : $file;
        }
        return $file;
    }
}

if (!function_exists('route_path')) {
    function route_path(?string $file = null)
    {
        return !is_null($file) ? config('route.path') . '/' . $file : config('route.path');
    }
}

/**
 * End paths
 */