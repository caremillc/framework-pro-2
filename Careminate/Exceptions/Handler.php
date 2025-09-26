<?php declare(strict_types=1);

namespace Careminate\Exceptions;

use Throwable;
use function Careminate\Support\logException;

class Handler
{
    public function report(Throwable $e): void { logException($e); }

    public function render(Throwable $e): void
    {
        $output = sprintf(
            "Exception: %s in %s:%d\nStack trace:\n%s\n",
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        if (php_sapi_name() === 'cli') echo $output;
        else echo "<pre>{$output}</pre>";
    }

    public function handle(Throwable $e): void
    {
        $this->report($e);
        $this->render($e);
        exit(1);
    }
}
