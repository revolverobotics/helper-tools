<?php

namespace Revolve\Microservice\Controllers;

use Illuminate\Http\Request;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Revolve\Microservice\Backend\BackendRequest;
use Revolve\Microservice\Backend\BackendResponse;
use Revolve\Microservice\Exceptions\BackendException;
use Revolve\Microservice\Traits\RequestValidatorTrait;

abstract class CustomController extends BaseController
{
    use AuthorizesRequests,
        DispatchesJobs,
        RequestValidatorTrait;

    protected $rq;

    protected $rsp;

    protected $backendResponse;

    public function __construct()
    {
        $this->rq = app()->request;
    }

    protected function success()
    {
        return $this->returnJson(200, $this->rsp);
    }

    protected function makeResponse(array $data, string $message = "")
    {
        $this->rsp = $data;

        $this->rsp['message'] = $message;
    }

    protected function returnJson($code = 200, $data = [], $headers = [])
    {
        $data['statusCode'] = $code;

        if (env('APP_DEBUG', false)) {
            $data[$this->appName()] = 'debug';
            $data['url'] = $this->rq->fullUrl();
            $data['SQL Queries'] = count(\DB::getQueryLog());
            $data['response_time'] = microtime(true) - LARAVEL_START;
        }

        $headers['Content-Type'] = 'application/json';

        return response()->json(
            $data,
            $code,
            $headers,
            JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES
        );
    }

    protected function backendResponse()
    {
        if ($this->backendRequest instanceof BackendRequest) {
            $this->backendResponse = new BackendResponse(
                $this->backendRequest->code(),
                $this->backendRequest->getResponse()
            );
        }

        // Exception handling can be found in BackendRequest::send()

        return response()->json(
            $this->backendResponse->content(),
            200,
            [],
            JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES
        );
    }

    protected function appName()
    {
        return trim(strrchr(base_path(), '/'), '/');
    }
}
