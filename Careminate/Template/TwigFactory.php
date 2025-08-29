<?php declare(strict_types=1);   
namespace Careminate\Template;

use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;


class TwigFactory
{
    public function __construct(
        private string $templatesPath
    ){}

    public function create(): Environment
    {
        // instantiate FileSystemLoader with templates path
        $loader = new FilesystemLoader($this->templatesPath);

        // instantiate Twig Environment with loader
        $twig = new Environment($loader, [
            'debug' => true,
            'cache' => false,
        ]);

        // add new twig session() function to Environment
        $twig->addExtension(new DebugExtension());

        return $twig;
    }

  
}
