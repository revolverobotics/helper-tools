<?php

namespace Revolve\Microservice\Exceptions;

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
    // Left this out:
    //        'HttpException' => $e->getCode(),

    protected $passThrough = [
        'BackendException',
        'ApiException'
    ];

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
            $headers = [];

            $response = $this->getExceptionType($e);

            if (!in_array($response['exception'], $this->passThrough)) {
                $response = $this->normalException($e, $response);
            } else {
                $response = $this->backendException($e, $response);
            }

            $this->addDebugData($e, $response);

            if (!isset($response['statusCode'])) {
                $response['statusCode'] = '500';
            }

            return response()->json(
                $response,
                $response['statusCode'],
                $headers,
                JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES
            );
        } catch (\Exception $e) {
            return parent::render($request, $e);
        }

        return parent::render($request, $e);
    }

    protected function getExceptionType(Exception $e)
    {
        $exceptionType = get_class($e);

        $exceptionType = str_replace(
            '\\',
            '',
            substr($exceptionType, strrpos($exceptionType, '\\'))
        );

        if (isset($this->statusCodes[$exceptionType])) {
            $statusCode = $this->statusCodes[$exceptionType];
        } elseif (in_array($exceptionType, $this->passThrough) &&
            isset(json_decode($e->getMessage(), true)['statusCode'])
        ) {
            $statusCode =
                json_decode($e->getMessage(), true)['statusCode'];
        } else {
            $statusCode = 500;
        }

        $response = [
            'statusCode' => $statusCode,
            'exception'  => $exceptionType
        ];

        return $response;
    }

    protected function normalException(Exception $e, $response)
    {
        $this->parsePayload($e, $response, function ($payload) use (&$response) {
            if (isset($payload['message'])) {
                $response['message'] = $payload['message'];
                unset($payload['message']);
            }

            $response['debug'] = $payload;
        });

        return $response;
    }

    protected function backendException(Exception $e, $response)
    {
        $this->parsePayload($e, $response, function ($payload) use (&$response) {
            $response = $payload;
        });

        return $response;
    }

    protected function parsePayload(Exception $e, &$response, $callback = null)
    {
        $payload = json_decode($e->getMessage(), true);

        if (is_null($payload)) {
            $response['message'] = $e->getMessage();
            $payload             = $e->getMessage();
        } else {
            if (is_callable($callback)) {
                $callback($payload);
            }
        }
    }

    protected function addDebugData(Exception $e, &$response)
    {
        if (env('APP_DEBUG', false)) {
            if (!isset($response['debug'])) {
                $response['debug'] = [];
            }

            $debug = [
                "{$e->getFile()}:{$e->getLine()}",
                $this->wrapStackTrace($e)
            ];

            array_push($response['debug'], $debug);
        }
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
