<?php declare(strict_types=1); 
namespace Careminate\Http;

use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;

class Kernel
{
    public function handle(Request $request): Response
    {
        $content = '<h1>Hello World</h1>';

        return new Response($content);
    }
}
