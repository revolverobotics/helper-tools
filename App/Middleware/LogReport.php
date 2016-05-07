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

        // NOTE: just temporary while devving
        if (strpos($request->url(), 'api/1.0/set-status') !== false) {
            return;
        }

        if (strpos($request->url(), 'api/1.0/kubi-list') !== false) {
            return;
        }

        if ($this->hasContentTypeHtml($this->response)) {
            return; // don't log html responses
        }

        if ($this->responseOk() && !$this->debugMode()) {
            return;
        }

        try {
            $this->makeFinalLog();

            $this->enableANSIOutput($this->finalLog);

            $this->outputFinalLog();
        } catch (\Exception $e) {
            \Log::critical($e->getMessage());
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
        $this->finalLog = 'Returned '.$this->response->status().
            ' | '.app()['appLog'];

        $this->finalLog .= PHP_EOL.PHP_EOL.print_r($this->request->all(), true);

        if (count($this->queryLog) > 0) {
            $this->finalLog .= PHP_EOL.'Queries:'.PHP_EOL.
                print_r($this->queryLog, true);
        }

        $this->finalLog .= PHP_EOL.'------------- Response -------------'.
            PHP_EOL.PHP_EOL.$this->response->headers;

        $this->finalLog .= PHP_EOL.$this->response->getContent();

        $this->finalLog .= PHP_EOL.PHP_EOL.
            '____________ End of Log ____________'.PHP_EOL.PHP_EOL;
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
            Log::{$errorCodes[$this->response->status()]}($this->finalLog);
        } else {
            Log::warning($this->finalLog);
        }
    }

    protected function hasContentTypeHtml($component)
    {
        $contentType = $component->headers->get('Content-Type');

        if (str_contains($contentType, 'text/html')) {
            return true;
        }

        return false;
    }

    protected function enableANSIOutput(&$string)
    {
        if (!env('ANSI', false)) {
            return false;
        }

        // Output regular text
        $string = "\033[0;37m\033[40m".$string;

        // Colorize line numbers, e.g.: (360)
        $string = preg_replace(
            "/\([0-9]*\)/",
            "\033[1;33m$0\033[0;39m\033[40m",
            $string
        );

        // Colorize trace indexes, .e.g.: #36
        $string = preg_replace(
            "/\#[0-9]*/",
            "\033[1;37m$0\033[0;39m\033[40m",
            $string
        );

        // Colorize forward slashes
        $string = preg_replace(
            "/\//",
            "\033[0;36m$0\033[0;39m\033[40m",
            $string
        );

        // Highlight line numbers
        $string = preg_replace(
            "/\:[0-9]*\"/",
            "\033[4;33m$0\033[0;39m\033[40m",
            $string
        );

        // Hide double quotes
        $string = preg_replace(
            "/\"/",
            "\033[8;32m$0\033[0;39m\033[40m",
            $string
        );

        // Hide commas at line endings
        $string = preg_replace(
            "/,\n/",
            "\033[8;32m$0\033[0;39m\033[40m",
            $string
        );

        // Highlight "__ End of Log __"
        // $string = preg_replace(
        //     "/(_*) End of Log (_*)/",
        //     "\033[7;33m$0\033[0;39m",
        //     $string
        // );

        // Replace double backslashes
        $string = str_replace('\\\\', '\\', $string);
    }

    protected function responseOk()
    {
        if ($this->response->status() == 200) {
            return true;
        }

        return false;
    }

    protected function debugMode()
    {
        if (env('APP_DEBUG', false)) {
            return true;
        }

        return false;
    }
}
