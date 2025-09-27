<?php declare(strict_types=1);

namespace Careminate\Routing;

use Closure;

class Route
{
    /**
     * @var array<string, array<int, array{path: string, handler: Closure|array}>>
     */
    private static array $routes = [
        'GET'    => [],
        'POST'   => [],
        'PUT'    => [],
        'PATCH'  => [],
        'DELETE' => [],
    ];

    /**
     * Register a GET route
     */
    public static function get(string $path, Closure|array|string $handler): void
    {
        self::addRoute('GET', $path, $handler);
    }

    public static function post(string $path, Closure|array|string $handler): void
    {
        self::addRoute('POST', $path, $handler);
    }

    public static function put(string $path, Closure|array|string $handler): void
    {
        self::addRoute('PUT', $path, $handler);
    }

    public static function patch(string $path, Closure|array|string $handler): void
    {
        self::addRoute('PATCH', $path, $handler);
    }

    public static function delete(string $path, Closure|array|string $handler): void
    {
        self::addRoute('DELETE', $path, $handler);
    }

    /**
     * Common route registration
     */
    private static function addRoute(string $method, string $path, Closure|array|string $handler): void
    {
        // Normalize single-action controller shortcut
        if (is_string($handler) && class_exists($handler)) {
            if (!method_exists($handler, '__invoke')) {
                throw new \InvalidArgumentException(
                    "Handler class {$handler} must implement __invoke() method."
                );
            }

            // Normalize into single-action form
            $handler = [$handler];
        }

        self::$routes[$method][] = [
            'path'    => $path,
            'handler' => $handler,
        ];
    }

    /**
     * Get all routes
     * @return array<string, array<int, array{path: string, handler: Closure|array}>>
     */
    public static function getRoutes(): array
    {
        return self::$routes;
    }
}
