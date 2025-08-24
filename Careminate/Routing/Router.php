<?php declare(strict_types=1);
namespace Careminate\Routing;

use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;
use Careminate\Routing\Contracts\RouterInterface;
use Psr\Container\ContainerInterface;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use Careminate\Exceptions\HttpException;
use Careminate\Exceptions\HttpRequestMethodException;

class Router implements RouterInterface
{
    private array $routes = [];

    public function setRoutes(array $routes): void
    {
        $this->routes = $routes;
    }

    public function dispatch(Request $request, ?ContainerInterface $container = null): array
    {
        $path = $request->getPathInfo();

        if ($path === '/favicon.ico') {
            return [[fn() => new Response('', 204), '__invoke'], []];
        }

        $dispatcher = simpleDispatcher(function (RouteCollector $r) {
            foreach ($this->routes as $method => $routes) {
                foreach ($routes as $route) {
                    $r->addRoute($method, $route['path'], $route['handler']);
                }
            }
        });

        $routeInfo = $dispatcher->dispatch($request->getMethod(), $path);

        switch ($routeInfo[0]) {
            case Dispatcher::FOUND:
                [$handler, $vars] = [$routeInfo[1], $routeInfo[2]];

                // Controller autowiring
                if (is_array($handler) && isset($handler[0], $handler[1]) && class_exists($handler[0]) && $container !== null) {
                    $handler[0] = $container->get($handler[0]);
                }

                // Single-action controller
                if (is_array($handler) && isset($handler[0]) && is_string($handler[0]) && !isset($handler[1]) && $container !== null) {
                    $handler[0] = $container->get($handler[0]);
                    $handler[1] = '__invoke';
                }
                // var_dump($handler); // => controller class + method
                // var_dump($handler[0]);  // Controller class 
                // var_dump($handler[1]);  // Controller method 
                // var_dump([$handler, $vars]); //=> controller class + method + parameter
                return [$handler, $vars];

            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowed = implode(', ', $routeInfo[1]);
                throw new HttpRequestMethodException("Allowed methods: $allowed", 405);

            default:
                throw new HttpException('Not Found', 404);
        }
    }
}
