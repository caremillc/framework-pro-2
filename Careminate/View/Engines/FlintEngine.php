<?php declare (strict_types = 1);
namespace Careminate\View\Engines;

use Careminate\View\Engines\Contracts\ViewEngineInterface;

class FlintEngine implements ViewEngineInterface
{
    protected FlintCompiler $compiler;

    public function __construct(array $config)
    {
        $this->compiler = new FlintCompiler(
            $config['paths'] ?? [],
            $config['cache'] ?? sys_get_temp_dir()
        );

        $this->registerDefaultDirectives();
    }

    public function render(string $view, array $data = []): string
    {
        return $this->compiler->render($view, $data);
    }

    protected function registerDefaultDirectives(): void
    {
        // @csrf
        $this->compiler->directive('csrf', fn() =>
            "<?php echo '<input type=\"hidden\" name=\"_token\" value=\"' . htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') . '\" />'; ?>"
        );

        // @auth / @endauth
        $this->compiler->directive('auth', fn() => "<?php if(Session::get('user')): ?>");
        $this->compiler->directive('endauth', fn() => "<?php endif; ?>");

        // @guest / @endguest
        $this->compiler->directive('guest', fn() => "<?php if(!Session::get('user')): ?>");
        $this->compiler->directive('endguest', fn() => "<?php endif; ?>");

        // @error('field')
        $this->compiler->directive('error', function ($expression) {
            $field = trim($expression, " \t\n\r\0\x0B'\""); // remove parentheses, quotes, whitespace
            return "<?php if(isset(\$errors['$field'])): ?><span class=\"error\"><?php echo \$errors['$field']; ?></span><?php endif; ?>";
        });

        // @dump / @dd
        $this->compiler->directive('dump', function ($expression) {
            $expr = trim($expression, " \t\n\r\0\x0B()");
            return "<?php var_dump($expr); ?>";
        });
        $this->compiler->directive('dd', function ($expression) {
            $expr = trim($expression, " \t\n\r\0\x0B()");
            return "<?php var_dump($expr); die; ?>";
        });

        // @asset('path/to/file')
        $this->compiler->directive('asset', function ($expression) {
            // Remove quotes and parentheses
            $path = trim($expression, " \t\n\r\0\x0B'\"()");
            return "<?php echo asset('$path'); ?>";
        });

        // @verbatim / @endverbatim handled internally by FlintCompiler
    }

}
