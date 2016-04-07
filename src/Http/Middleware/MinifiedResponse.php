<?php

namespace Jchedev\Laravel\Http\Middleware;

use Illuminate\Http\Response;

class MinifiedResponse
{
    public function handle($request, \Closure $next)
    {
        $final = $next($request);

        if ($final instanceof Response) {
            $final->setContent(minify_html($final->getOriginalContent()));
        }

        return $final;
    }
}