<?php

namespace App\Submodules\ToolsLaravelMicroservice\App\Middleware;

use Closure;

class NoSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        \Config::set('session.driver', 'array');
        return $next($request);
    }
}
