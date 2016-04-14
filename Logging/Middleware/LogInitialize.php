<?php

namespace App\Submodules\ToolsLaravelMicroservice\Logging\Middleware;

use Redis;
use Closure;

class LogInitialize
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
        if (strpos($request->url(), 'admin/logs') === false) {

			$nowformatted = date("Y-m-d H:i:s", time());

            $logStart = $request;

            app()->singleton('appLog', function($app) use ($logStart)
            {
                return ($logStart);
            });

            \DB::connection()->enableQueryLog();
        }
        return $next($request);
    }
}
