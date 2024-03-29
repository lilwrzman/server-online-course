<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function authenticate($request, array $guards)
    {
        if ($this->auth->guard('api')->check()) {
            return $this->auth->shouldUse('api');
        }

        $this->unauthenticated($request, $guards);
    }

    protected function unauthenticated($request, array $guards)
    {
        abort(response()->json(['error' => 'Unauthenticated.'], 401));
    }
}
