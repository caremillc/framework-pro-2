<?php declare (strict_types = 1);
namespace Careminate\View\Engines;

class FlintCompiler
{
    protected array $paths;
    protected string $cachePath;

    protected array $directives   = [];
    protected array $sections     = [];
    protected array $sectionStack = [];
    protected array $stacks       = [];

    protected ?string $parent = null;

    public function __construct(array $paths, string $cachePath)
    {
        $this->paths     = $paths;
        $this->cachePath = $cachePath;
    }

    public function directive(string $name, callable $handler): void
    {
        $this->directives[$name] = $handler;
    }

    public function render(string $view, array $data = []): string
    {
        $path = $this->findViewPath($view);

        if (! file_exists($path)) {
            throw new \RuntimeException("View [$view] not found.");
        }

        $contents = file_get_contents($path);
        $compiled = $this->compile($contents);

        extract($data, EXTR_SKIP);

        ob_start();
        eval('?>' . $compiled);
        $output = ob_get_clean();

        // If parent template exists, render parent with collected sections
        // if ($this->parent) {
        //     $parent = $this->parent;
        //     $this->parent = null; // reset
        //     $data['sections'] = $this->sections;
        //     $data['stacks']   = $this->stacks;
        //     $output = $this->render($parent, $data);
        // }

        if ($this->parent) {
            $parent       = $this->parent;
            $this->parent = null; // reset

            // ✅ carry forward sections & stacks into this instance
            $this->sections = array_merge($this->sections, $data['sections'] ?? []);
            $this->stacks   = array_merge($this->stacks, $data['stacks'] ?? []);

            $output = $this->render($parent, $data);
        }

        return $output;
    }

    protected function compile(string $contents): string
    {
        // Handle @extends
        $contents = preg_replace_callback('/@extends\s*\(\s*[\'"](.+?)[\'"]\s*\)/', function ($m) {
            $this->parent = $m[1];
            return '';
        }, $contents);

        // Extract verbatim blocks
        [$contents, $verbatimMap] = $this->extractVerbatimBlocks($contents);

        // Compile @php ... @endphp
        $contents = preg_replace_callback('/@php\s*(.*?)\s*@endphp/s', fn($m) => "<?php {$m[1]} ?>", $contents);

        // Compile core directives
        $replacements = [
            '/@if\s*\((.*?)\)/'      => '<?php if ($1): ?>',
            '/@elseif\s*\((.*?)\)/'  => '<?php elseif ($1): ?>',
            '/@else/'                => '<?php else: ?>',
            '/@endif/'               => '<?php endif; ?>',

            '/@foreach\s*\((.*?)\)/' => '<?php foreach ($1): ?>',
            '/@endforeach/'          => '<?php endforeach; ?>',

            '/@for\s*\((.*?)\)/'     => '<?php for ($1): ?>',
            '/@endfor/'              => '<?php endfor; ?>',

            '/@while\s*\((.*?)\)/'   => '<?php while ($1): ?>',
            '/@endwhile/'            => '<?php endwhile; ?>',

            '/@switch\s*\((.*?)\)/'  => '<?php switch ($1): ?>',
            '/@case\s*\((.*?)\)/'    => '<?php case $1: ?>',
            '/@default/'             => '<?php default: ?>',
            '/@endswitch/'           => '<?php endswitch; ?>',

            '/@isset\s*\((.*?)\)/'   => '<?php if(isset($1)): ?>',
            '/@endisset/'            => '<?php endif; ?>',
            '/@empty\s*\((.*?)\)/'   => '<?php if(empty($1)): ?>',
            '/@endempty/'            => '<?php endif; ?>',
        ];

        foreach ($replacements as $pattern => $replace) {
            $contents = preg_replace($pattern, $replace, $contents);
        }

        // @break / @continue
        $contents = preg_replace_callback('/@break(\s*\((.*?)\))?/', fn($m) => ! empty($m[2]) ? "<?php if({$m[2]}) break; ?>" : "<?php break; ?>", $contents);
        $contents = preg_replace_callback('/@continue(\s*\((.*?)\))?/', fn($m) => ! empty($m[2]) ? "<?php if({$m[2]}) continue; ?>" : "<?php continue; ?>", $contents);

        // Custom directives
        foreach ($this->directives as $name => $handler) {
            $pattern  = '/@' . preg_quote($name, '/') . '(\(.*?\))?/s';
            $contents = preg_replace_callback($pattern, function ($matches) use ($handler) {
                $expression = $matches[1] ?? '';
                return call_user_func($handler, $expression);
            }, $contents);
        }

        // Compile echos
        $contents = preg_replace_callback('/\{!!\s*(.+?)\s*!!\}/s', fn($m) => "<?php echo {$m[1]}; ?>", $contents);
        $contents = preg_replace_callback('/\{\{\s*(.+?)\s*\}\}/s', fn($m) => "<?php echo htmlspecialchars({$m[1]}, ENT_QUOTES, 'UTF-8'); ?>", $contents);

        // Sections and stacks (patched)
        $contents = $this->compileSections($contents);

        // Restore verbatim blocks
        $contents = $this->restoreVerbatimBlocks($contents, $verbatimMap);

        return $contents;
    }

    protected function compileSections(string $contents): string
    {
        // @section('name') ... @endsection
        $contents = preg_replace_callback('/@section\s*\(\s*[\'"](.+?)[\'"]\s*\)/', function ($m) {
            $this->sectionStack[] = $m[1];
            return "<?php ob_start(); ?>";
        }, $contents);

        $contents = preg_replace_callback('/@endsection/', function () {
            $name     = array_pop($this->sectionStack);
            $safeName = var_export($name, true);
            return "<?php \$this->sections[$safeName] = ob_get_clean(); ?>";
        }, $contents);

        // @yield('name')
        $contents = preg_replace_callback('/@yield\s*\(\s*[\'"](.+?)[\'"]\s*\)/', function ($m) {
            $safeName = var_export($m[1], true);
            return "<?php echo \$this->sections[$safeName] ?? ''; ?>";
        }, $contents);

        // @push('name') ... @endpush
        $contents = preg_replace_callback('/@push\s*\(\s*[\'"](.+?)[\'"]\s*\)/', function ($m) {
            $this->sectionStack[] = $m[1];
            return "<?php ob_start(); ?>";
        }, $contents);

        $contents = preg_replace_callback('/@endpush/', function () {
            $name     = array_pop($this->sectionStack);
            $safeName = var_export($name, true);
            return "<?php \$this->stacks[$safeName][] = ob_get_clean(); ?>";
        }, $contents);

        // @stack('name')
        $contents = preg_replace_callback('/@stack\s*\(\s*[\'"](.+?)[\'"]\s*\)/', function ($m) {
            $safeName = var_export($m[1], true);
            return "<?php if(isset(\$this->stacks[$safeName])) echo implode('', \$this->stacks[$safeName]); ?>";
        }, $contents);

        return $contents;
    }

    protected function extractVerbatimBlocks(string $contents): array
    {
        $map = [];
        $i   = 0;

        // @verbatim ... @endverbatim
        $contents = preg_replace_callback('/@verbatim(.*?)@endverbatim/s', function ($m) use (&$map, &$i) {
            $key       = "__CAREMI_VERBATIM_BLOCK_{$i}__";
            $map[$key] = $m[1];
            $i++;
            return $key;
        }, $contents);

        // <script>...</script> and <style>...</style>
        $contents = preg_replace_callback('/<(script|style)(.*?)>(.*?)<\/\1>/is', function ($m) use (&$map, &$i) {
            $key       = "__CAREMI_VERBATIM_BLOCK_{$i}__";
            $map[$key] = "<{$m[1]}{$m[2]}>{$m[3]}</{$m[1]}>";
            $i++;
            return $key;
        }, $contents);

        return [$contents, $map];
    }

    protected function restoreVerbatimBlocks(string $contents, array $map): string
    {
        foreach ($map as $key => $raw) {
            $contents = str_replace($key, $raw, $contents);
        }
        return $contents;
    }

    protected function findViewPath(string $view): string
    {
        $relative = str_replace('.', '/', $view);

        $paths      = config('view.paths', [base_path('templates/views')]);
        $extensions = config('view.extensions', ['.caremi.php', '.flint.php', '.php']);

        $tried = [];
        foreach ($paths as $path) {
            foreach ($extensions as $ext) {
                $full    = rtrim($path, '/\\') . '/' . $relative . $ext;
                $tried[] = $full;
                if (file_exists($full)) {
                    return $full;
                }
            }
        }

        $msg = "View [$view] not found.\nTried paths:\n - " . implode("\n - ", $tried);
        throw new \RuntimeException($msg);
    }
}
