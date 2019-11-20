<?php

namespace Jchedev\Laravel\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;

class AuthNotRequired extends Authenticate
{
    /**
     * Sometimes we want to allow authentication without making it mandatory
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param mixed ...$guards
     * @return mixed
     */
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