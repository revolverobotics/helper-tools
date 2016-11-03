<?php

namespace Revolve\Microservice\Http\Middleware;

use DB;
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
        if (strpos($request->url(), 'admin/log-viewer') === false) {
            $nowformatted = date("Y-m-d H:i:s", time());

            $logStart = $request;

            app()->singleton('appLog', function ($app) use ($logStart) {
                return ($logStart);
            });

            DB::connection()->enableQueryLog();
        }
        return $next($request);
    }
}
