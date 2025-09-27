<?php declare(strict_types=1);
namespace Careminate\Routing\Contracts;

use Careminate\Http\Requests\Request;
use Psr\Container\ContainerInterface;

interface RouterInterface
{
    public function dispatch(Request $request, ContainerInterface $container);

    public function setRoutes(array $routes): void;
}