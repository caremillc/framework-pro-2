<?php declare(strict_types=1);
namespace Careminate\Routing\Contracts;

use Careminate\Http\Requests\Request;

interface RouterInterface
{
    public function dispatch(Request $request);
}
