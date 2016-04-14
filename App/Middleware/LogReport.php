<?php

namespace App\Submodules\ToolsLaravelMicroservice\App\Middleware;

use DB;
use Log;
use Closure;

class LogReport
{
    protected $request;

    protected $response;

    protected $queryLog;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        $this->request = $request;
        $this->response = $response;
        $this->queryLog = DB::getQueryLog();

        if ($this->hasContentTypeHtml($this->response))
            return; // don't log html responses

        if ($this->responseOk() && !$this->debugMode())
            return;

        try {
            $finalLog = 'Returned ' . $this->response->status() .
                ' | ' . app()['appLog'];

            $finalLog .= PHP_EOL . PHP_EOL . print_r($request->all(), true);

            if (count($this->queryLog) > 0)
                $finalLog .= PHP_EOL . 'Queries:' . PHP_EOL .
                    print_r($this->queryLog, true);

            $finalLog .= PHP_EOL . '------------- Response -------------' .
                PHP_EOL . PHP_EOL . $this->response->headers;
            $finalLog .= PHP_EOL . $this->response->getContent();

            $finalLog .= PHP_EOL . PHP_EOL . '____________ End of Log ____________' .
                PHP_EOL . PHP_EOL;

            switch ($this->response->status()) {

                case 200:
                    Log::debug($finalLog);
                    break;

                case 302:
                    // don't log redirects
                    break;

                case 400:
                    Log::notice($finalLog);
                    break;

                case 401:
                    Log::warning($finalLog);
                    break;

                case 403:
                    Log::warning($finalLog);
                    break;

                case 404:
                    Log::notice($finalLog);
                    break;

                case 405:
                    Log::notice($finalLog);
                    break;

                case 500:
                    Log::error($finalLog);
                    break;

                default:
                    Log::warning($finalLog);

            }
        } catch (\Exception $e) {
            \Log::critical('Couldn\'t get app log.');
        }
    }

    private function hasContentTypeHtml($component)
    {
        $contentType = $component->headers->get('Content-Type');

        if (str_contains($contentType, 'text/html'))
            return true;

        return false;
    }

    private function responseOk()
    {
        if ($this->response->status() == 200)
            return true;

        return false;
    }

    private function debugMode()
    {
        if (env('APP_DEBUG', false))
            return true;

        return false;
    }
}
