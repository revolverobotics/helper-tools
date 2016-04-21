<?php

namespace App\Submodules\ToolsLaravelMicroservice\App\Exceptions;

use Helper;
use Exception;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A mapping of exception types to HTTP status codes.
     *
     * @var array
     */
    protected $statusCodes = [
        'BadRequestHttpException'           => 400,
        'FatalErrorException'               => 500,
        'MethodNotAllowedHttpException'     => 405,
        'ModelNotFoundException'            => 404,
        'NotFoundHttpException'             => 404,
        'NotReadableException'              => 400,
        'TooManyRequestsHttpException'      => 408,
        'UnauthorizedHttpException'         => 401,
        'ValidationException'               => 400,
    ];
    // Intervention\Image\Exception\NotReadableException
    // Left these out:
    //        'HttpException' => $e->getCode(),
    //        'BackendException' => $e->getCode()

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        return parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        try {
            $exceptionType = get_class($e);

            $exceptionType = substr(
                $exceptionType,
                strrpos($exceptionType, '\\')+1
            );

            if (isset($this->statusCodes[$exceptionType])) {
                $statusCode = $this->statusCodes[$exceptionType];
            } else {
                $statusCode = 500;
            }

            $response = [
                'statusCode' => $statusCode,
                'exception'  => $exceptionType
            ];

            $message = json_decode($e->getMessage());

            if (is_null($message)) {
                $response['message'] = $e->getMessage();
                $message             = $e->getMessage();
            } else {
                $response['data']    = $message;
            }

            $headers = [];

            if (env('APP_DEBUG', false)) {
                $where                   = "{$e->getFile()}:{$e->getLine()}";
                $response['at']          = $where;
                $response['stack trace'] = $this->wrapStackTrace($e);
                $response['previous']    = $e->getPrevious();
            }

            return response()->json(
                $response,
                $statusCode,
                $headers,
                JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES
            );
        } catch (\Exception $e) {
            return parent::render($request, $e);
        }

        return parent::render($request, $e);
    }

    protected function wrapStackTrace($e)
    {
        $stackArray = explode("\n", $e->__toString());
        unset($stackArray[0]);
        unset($stackArray[1]);

        $count = 0;
        $newStackArray = [];

        foreach ($stackArray as $line) {
            if (strlen($line) > 80) {
                $line = str_split($line, 80);
                foreach ($line as $subLine) {
                    array_push($newStackArray, $subLine);
                }
            } else {
                array_push($newStackArray, $line);
            }

            $count++;

            if (env('APP_DEBUG_LEVEL', 1) <= 1 && $count > 5) {
                break;
            }
        }

        return $newStackArray;
    }
}
