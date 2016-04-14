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

    protected $finalLog;

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
        $this->init($request, $response);

        if ($this->hasContentTypeHtml($this->response))
            return; // don't log html responses

        if ($this->responseOk() && !$this->debugMode())
            return;

        try {
            $this->makeFinalLog();
            $this->outputFinalLog();
        } catch (\Exception $e) {
            \Log::critical('Couldn\'t get app log.');
        }
    }

    protected function init($request, $response)
    {
        $this->request = $request;
        $this->response = $response;
        $this->queryLog = DB::getQueryLog();
    }

    protected function makeFinalLog()
    {
        $finalLog = 'Returned '.$this->response->status().' | '.app()['appLog'];

        $finalLog .= PHP_EOL.PHP_EOL.print_r($request->all(), true);

        if (count($this->queryLog) > 0)
            $finalLog .= PHP_EOL.'Queries:'.PHP_EOL.
                print_r($this->queryLog, true);

        $finalLog .= PHP_EOL.'------------- Response -------------'.
            PHP_EOL.PHP_EOL.$this->response->headers;
        $finalLog .= PHP_EOL.$this->response->getContent();

        $finalLog .= PHP_EOL.PHP_EOL.'____________ End of Log ____________'.
            PHP_EOL.PHP_EOL;
    }

    protected function outputFinalLog()
    {
        $errorCodes = [
            200 => 'debug',
            400 => 'notice',
            404 => 'notice',
            405 => 'notice',
            401 => 'warning',
            403 => 'warning',
            500 => 'error'
        ];

        if (array_key_exists($this->response->status(), $errorCodes)) {
            Log::$errorCodes[$this->response->status()]($this->finalLog);
        } else {
            Log::warning($this->finalLog);
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
