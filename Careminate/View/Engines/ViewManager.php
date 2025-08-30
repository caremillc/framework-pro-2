<?php declare(strict_types=1);
namespace Careminate\View\Engines;

use Careminate\View\Engines\Contracts\ViewEngineInterface;

class ViewManager implements ViewEngineInterface
{
    protected ViewEngineInterface $engine;

    public function __construct(array $config)
    {
        $engine = strtolower($config['engine'] ?? 'flint');

        switch ($engine) {
            case 'flint':
                $this->engine = new FlintEngine($config);
                break;
            case 'twig':
                $this->engine = new TwigEngine($config);
                break;
            case 'plates':
                $this->engine = new PlatesEngine($config);
                break;
            default:
                throw new \InvalidArgumentException("Unsupported view engine: {$engine}");
        }
    }

    public function render(string $view, array $data = []): string
    {
        return $this->engine->render($view, $data);
    }
}
