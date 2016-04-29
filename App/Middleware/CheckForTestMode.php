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
        if ($request->has('test_key')) {
            if (file_exists(base_path() . '/.env.testing')) {
                \Dotenv::load(base_path(), '.env.testing');
            }

            $inputKey = $request->input('test_key', str_random(32));
            $envKey = env('TEST_KEY', str_random(32));
            if ($inputKey != $envKey) {
                throw new \FatalErrorException('Get outta here.');
            }
        }

        return $next($request);
    }
}
