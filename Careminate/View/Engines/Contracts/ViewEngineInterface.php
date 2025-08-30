<?php declare(strict_types=1);
namespace Careminate\View\Engines\Contracts;

interface ViewEngineInterface
{
    public function render(string $view, array $data = []): string;
}