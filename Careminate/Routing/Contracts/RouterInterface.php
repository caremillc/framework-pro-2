<?php declare(strict_types=1);
namespace Careminate\Routing\Contracts;

use Careminate\Http\Requests\Request;
use Psr\Container\ContainerInterface;

interface RouterInterface
{
    /**
     * Dispatch the request to a route handler.
     * 
     * @param Request $request
     * @param ContainerInterface|null $container
     * @return array [handler, routeVariables]
     */
    public function dispatch(Request $request, ?ContainerInterface $container = null): array;

    /**
     * Inject routes into the router
     * 
     * @param array $routes
     */
    public function setRoutes(array $routes): void;
}
