<?php

namespace Revolve\Microservice\Http\Middleware;

use Closure;
use Dotenv\Dotenv;

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
            This is necessary for some of our services to perform cleanup
            after running their own unit tests
        */

        if ($request->has('test_key')) {
            if (file_exists(base_path() . '/.env.testing')) {
		$dotenv = new Dotenv(dirname(__DIR__), '.env.testing');
                $dotenv->load();
            }

            if (!config('tests.key')) {
		throw new \Exception(
		    'This app must have a TEST_KEY configured within '.
		    'config/tests.php such that calling config(\'tests.key\')'.
		    'returns a valid string'
		);
	    }

            $inputKey = $request->input('test_key', str_random(32));

            if ($inputKey != config('tests.key')) {
                throw new \FatalErrorException('Get outta here.');
            }
        }

        return $next($request);
    }
}
