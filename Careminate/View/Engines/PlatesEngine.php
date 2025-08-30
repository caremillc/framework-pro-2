<?php declare(strict_types=1);
namespace Careminate\View\Engines;

use League\Plates\Engine;
use Careminate\View\Engines\Contracts\ViewEngineInterface;

class PlatesEngine implements ViewEngineInterface
{
    protected Engine $plates;

    public function __construct(array $config)
    {
        $this->plates = new Engine($config['paths'][0] ?? __DIR__);
    }

    public function render(string $view, array $data = []): string
    {
        return $this->plates->render($view, $data);
    }
}


