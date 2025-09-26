<?php declare (strict_types = 1);

use Careminate\Logging\Logger;
use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;

// Just include the file at the top of your script
// require_once __DIR__ . '/debug_functions.php';

/**
 * Dump and die (debugging helper).
 */
// if (!function_exists('dd')) {
//     function dd(...$vars): void
//     {
//         foreach ($vars as $v) {
//             echo '<pre>';
//             var_dump($v);
//             echo '</pre>';
//         }
//         die(1);
//     }
// }

if (! function_exists('value')) {
    function value(mixed $value, ...$args): mixed
    {
        return is_callable($value) ? $value(...$args) : $value;
    }
}

if (!function_exists('env')) {
    /**
     * Get an environment variable, or return the default value if not found.
     *
     * Supports various data types.
     *
     * @param string $key The name of the environment variable.
     * @param mixed $default The default value to return if the environment variable is not found.
     * @return mixed The value of the environment variable or the default value.
     */
    function env(string $key, $default = null)
    {
        // check superglobals first, then getenv() reliably
        if (array_key_exists($key, $_ENV)) {
            $value = $_ENV[$key];
        } elseif (array_key_exists($key, $_SERVER)) {
            $value = $_SERVER[$key];
        } else {
            $g = getenv($key);
            $value = ($g !== false) ? $g : $default;
        }

        if (!is_string($value)) {
            return $value;
        }

        $trimmedValue = trim($value);

        return match (strtolower($trimmedValue)) {
            'true' => true,
            'false' => false,
            'null' => null,
            'empty' => '',
            default => is_numeric($trimmedValue) ? (str_contains($trimmedValue, '.') ? (float)$trimmedValue : (int)$trimmedValue) : (
                preg_match('/^[\[{].*[\]}]$/', $trimmedValue) ? (json_decode($trimmedValue, true) ?? $trimmedValue) : $trimmedValue
            )
        };
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
 * start paths
 */
 if (! function_exists('public_path')) {
    function public_path(?string $file = null): string
    {
        return base_path('public' . ($file ? '/' . $file : ''));
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return BASE_PATH . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (! function_exists('app_path')) {
    function app_path(?string $file = null): string
    {
        return base_path('app' . ($file ? '/' . $file : ''));
    }
}

if (! function_exists('config_path')) {
    function config_path(?string $file = null): string
    {
        return base_path('config' . ($file ? '/' . $file : ''));
    }
}

if (! function_exists('storage_path')) {
    function storage_path(?string $file = null): string
    {
        return base_path('storage' . ($file ? '/' . $file : ''));
    }
}

if (! function_exists('resource_path')) {
    function resource_path(?string $file = null): string
    {
        return base_path('resources' . ($file ? '/' . $file : ''));
    }
}

if (!function_exists('route_path')) {
    function route_path(string $path = ''): string
    {
        return base_path('routes' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
    }
}

if (!function_exists('config')) {
    function config(string $key, $default = null)
    {
        static $config = null;
        
        if ($config === null) {
            $configPath = base_path('config');
            $config = [];
            
            foreach (glob($configPath . '/*.php') as $file) {
                $name = pathinfo($file, PATHINFO_FILENAME);
                $config[$name] = require $file;
            }
        }
        
        return array_get($config, $key, $default);
    }
}

if (!function_exists('array_get')) {
    function array_get(array $array, string $key, $default = null)
    {
        if (isset($array[$key])) {
            return $array[$key];
        }
        
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }
        
        return $array;
    }
}


if (!function_exists('logger')) {
    function logger(?string $channel = null): Logger
    {
        static $instances = [];
        $config = config('log');
        $channel = $channel ?? $config['default'] ?? 'default';

        if (!isset($instances[$channel])) {
            $chanConf = $config['channels'][$channel] ?? [];
            $chanConf['channel'] = $channel;
            $instances[$channel] = new Logger($chanConf);
        }

        return $instances[$channel];
    }
}

if (!function_exists('logException')) {
    function logException(Throwable $e, ?string $channel = null): void
    {
        $config = config('log');
        $exceptionMap = $config['exception_map'] ?? [];
        $level = 'error';
        $ch = $channel ?? $config['default'] ?? 'default';

        foreach ($exceptionMap as $class => [$lvl, $logChannel, $alert]) {
            if ($e instanceof $class) { $level = $lvl; $ch = $logChannel; break; }
        }

        $context = [
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
            'code'  => $e->getCode(),
            'trace' => $e->getTraceAsString(),
        ];

        logger($ch)->{$level}($e->getMessage(), $context);
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
 * End Response Helper Function
 */