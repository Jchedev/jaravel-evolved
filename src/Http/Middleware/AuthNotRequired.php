<?php

namespace Jchedev\Laravel\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;

class AuthNotRequired extends Authenticate
{
    public function handle($request, \Closure $next, ...$guards)
    {
        try {
            $this->authenticate($request, $guards);
        }
        catch (AuthenticationException $exception) {
            // Because it is optional, we don't generate the exception at this point.
        }

        return $next($request);
    }
}