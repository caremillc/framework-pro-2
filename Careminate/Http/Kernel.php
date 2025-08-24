<?php declare (strict_types = 1);
namespace Careminate\Http;

use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;
use Careminate\Routing\Contracts\RouterInterface;
use Careminate\Routing\Route;
use Psr\Container\ContainerInterface;

class Kernel
{
    public function __construct(
        private RouterInterface $router,
        private ContainerInterface $container
    ) {}

    public function handle(Request $request): Response
    {
        try {
            [$handler, $vars] = $this->router->dispatch($request, $this->container);

            $args     = array_values($vars);
            $response = call_user_func_array($handler, $args);

            if (! $response instanceof Response) {
                $response = new Response((string) $response);
            }

        } catch (\Exception $e) {
            $response = new Response($e->getMessage(), 400);
        }

        return $response;
    }

    public function boot(): void
    {
        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        foreach (['web', 'api', 'console'] as $file) {
            $this->loadRouteFile($file);
        }

        $this->router->setRoutes(Route::getRoutes());
    }

    protected function loadRouteFile(string $name): void
    {
        $path = BASE_PATH . "/routes/{$name}.php";
        if (file_exists($path)) {
            require $path;
        }
    }
}
