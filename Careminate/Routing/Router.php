<?php declare (strict_types = 1);

namespace Careminate\Routing;

use Careminate\Exceptions\HttpException;
use Careminate\Exceptions\HttpRequestMethodException;
use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;
use Careminate\Routing\Contracts\RouterInterface;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

class Router implements RouterInterface
{
    public function dispatch(Request $request): array
    {
        $routeInfo = $this->extractRouteInfo($request);

        // If routeInfo is null (favicon or ignored route), return a no-content response
        if ($routeInfo === null) {
            return [[fn() => new Response('', 204), '__invoke'], []];
        }

        [$handler, $vars] = $routeInfo;

        /**
         * 🔹 Case 1: Closure route
         * Example: Route::get('/hello', fn($name) => new Response(...));
         */
        if ($handler instanceof \Closure) {
            return [[$handler, '__invoke'], $vars];
        }

        /**
         * 🔹 Case 2: Controller + method
         * Example: Route::get('/', [HomeController::class, 'index']);
         */
        if (is_array($handler) && isset($handler[0], $handler[1])) {
            [$controller, $method] = $handler;

            if (! class_exists($controller)) {
                throw new \InvalidArgumentException("Controller {$controller} does not exist.");
            }
            if (! method_exists($controller, $method)) {
                throw new \InvalidArgumentException("Method {$method} not found in controller {$controller}.");
            }

            return [[new $controller, $method], $vars];
        }

        /**
         * 🔹 Case 3: Single-action controller
         * Example: Route::get('/contact', [InvokeController::class]);
         * Will map to __invoke()
         */
        if (is_array($handler) && isset($handler[0]) && is_string($handler[0]) && ! isset($handler[1])) {
            $controller = $handler[0];

            if (! class_exists($controller)) {
                throw new \InvalidArgumentException("Controller {$controller} does not exist.");
            }
            if (! method_exists($controller, '__invoke')) {
                throw new \InvalidArgumentException("Controller {$controller} must implement __invoke() for single-action usage.");
            }

            return [[new $controller, '__invoke'], $vars];
        }

        throw new \InvalidArgumentException('Invalid route handler definition.');
    }

    private function extractRouteInfo(Request $request): array | null
    {
        $requestedPath = $request->getPathInfo();

        if ($requestedPath === '/favicon.ico') {
            return null; // gracefully handled above
        }

        $dispatcher = simpleDispatcher(function (RouteCollector $routeCollector) {
            foreach (Route::getRoutes() as $method => $routes) {
                foreach ($routes as $route) {
                    $routeCollector->addRoute($method, $route['path'], $route['handler']);
                }
            }
        });

        $routeInfo = $dispatcher->dispatch(
            $request->getMethod(),
            $requestedPath
        );

        switch ($routeInfo[0]) {
            case Dispatcher::FOUND:
                return [$routeInfo[1], $routeInfo[2]];
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = implode(', ', $routeInfo[1]);
                throw new HttpRequestMethodException("The allowed methods are $allowedMethods", 405);
            default:
                throw new HttpException('Not found', 404);
        }
    }
}
