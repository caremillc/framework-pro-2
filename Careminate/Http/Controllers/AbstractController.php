<?php declare(strict_types=1);
namespace Careminate\Http\Controllers;

use Psr\Container\ContainerInterface;
use Careminate\Http\Responses\Response;

abstract class AbstractController
{
    protected ?ContainerInterface $container = null;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    public function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        // Use global view() helper → supports multiple engines
        $content = view($view, $parameters);

        $response ??= new Response();
        $response->setContent($content);

        return $response;
    }
}
