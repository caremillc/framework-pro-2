<?php declare(strict_types=1);
namespace Careminate\View\Engines;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Careminate\View\Engines\Contracts\ViewEngineInterface;


class TwigEngine implements ViewEngineInterface
{
    protected Environment $twig;

    public function __construct(array $config)
    {
        $loader = new FilesystemLoader($config['paths'] ?? []);
        $this->twig = new Environment($loader, [
            'cache' => $config['cache'] ?? false,
            'auto_reload' => true,
        ]);
    }

    public function render(string $view, array $data = []): string
    {
        return $this->twig->render("{$view}.twig", $data);
    }
}


