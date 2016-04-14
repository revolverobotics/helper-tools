<?php

namespace App\Submodules\ToolsLaravelMicroservice\App\Middleware;

use Closure;

class CheckForTestMode
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
        /*
            This is necessary for frontend servers to perform cleanup
            after running their own unit tests
        */
        if ($request->has('test_key')):

            if (file_exists(base_path() . '/.env.testing'))
                \Dotenv::load(base_path(), '.env.testing');

            if ($request->input('test_key', 'incorrect') != env('TEST_KEY', 'no key'))
                throw new \FatalErrorException('Get outta here.');

        endif;

        return $next($request);
    }
}
