<?php declare (strict_types = 1);

namespace Careminate\Routing;

use Careminate\Exceptions\HttpException;
use Careminate\Exceptions\HttpRequestMethodException;
use Careminate\Http\Requests\Request;
use Careminate\Routing\Contracts\RouterInterface;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use Psr\Container\ContainerInterface;

class Router implements RouterInterface
{
    private array $routes = [];

    public function setRoutes(array $routes): void
    {
        $this->routes = $routes;
    }

    public function dispatch(Request $request, ContainerInterface $container): array
    {
        $routeInfo = $this->extractRouteInfo($request);

        // If routeInfo is null (e.g., for favicon or similar), short-circuit gracefully
        if ($routeInfo === null) {
            return [[fn() => new \Careminate\Http\Responses\Response('', 204), '__invoke'], []];
        }

        [$handler, $vars] = $routeInfo;

        // 🔹 Case 1: Closure
        if ($handler instanceof \Closure) {
            return [[$handler, '__invoke'], $vars];
        }

        // 🔹 Case 2: [Controller::class, 'method']
        if (is_array($handler) && count($handler) === 2 && is_string($handler[0]) && is_string($handler[1])) {
            [$controller, $method] = $handler;

            // Use the container to resolve the controller (with dependencies)
            $controllerInstance = $container->get($controller);

            return [[$controllerInstance, $method], $vars];
        }

        // Single-action controller [Controller::class]
        if (is_array($handler) && count($handler) === 1 && is_string($handler[0])) {
            $controller = new $handler[0];
            return [[$controller, '__invoke'], $vars];
        }

        // Normal controller [Controller::class, 'method']
        if (is_array($handler) && isset($handler[0], $handler[1])
            && is_string($handler[0]) && is_string($handler[1])) {
            [$controller, $method] = $handler;
            return [[new $controller, $method], $vars];
        }

        throw new \InvalidArgumentException('Invalid route handler definition.');
    }

    private function extractRouteInfo(Request $request): array | null
    {
        $requestedPath = $request->getPathInfo();

        if ($requestedPath === '/favicon.ico') {
            return null;
        }

        $dispatcher = simpleDispatcher(function (RouteCollector $routeCollector) {
            foreach ($this->routes as $method => $routes) {
                foreach ($routes as $route) {
                    $routeCollector->addRoute($method, $route['path'], $route['handler']);
                }
            }
        });

        $routeInfo = $dispatcher->dispatch($request->getMethod(), $requestedPath);

        return match ($routeInfo[0]) {
            Dispatcher::FOUND              => [$routeInfo[1], $routeInfo[2]],
            Dispatcher::METHOD_NOT_ALLOWED => throw new HttpRequestMethodException(
                "The allowed methods are " . implode(', ', $routeInfo[1]), 405
            ),
            default                        => throw new HttpException('Not found', 404),
        };
    }
}
