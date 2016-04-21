<?php
/*
    For providing uniform request/response handling across our different
    controllers
*/
namespace App\Submodules\ToolsLaravelMicroservice\App\Traits;

use App\Submodules\ToolsLaravelMicroservice\App\RRHelper;
use App\Submodules\ToolsLaravelMicroservice\App\Exceptions\BackendException;

trait RequestResponseTrait
{
    protected $request;

    protected $response;

    protected $responseMessage;

    public function initialize(\Illuminate\Http\Request $request)
    {
        $this->request = $request;
    }

    public function success()
    {
        return RRHelper::prettyJson(200, $this->response);
    }

    public function error($code, $response)
    {
        \Log::debug('error called');
        return RRHelper::prettyJson($code, $response);
    }

    public function makeResponse($data, $message="")
    {
        if (!is_array($data) && !is_null($data))
            throw new \FatalErrorException(
                'Argument 1 (data) of makeResponse must be passed as an array.'
            );

        $this->response = $data;

        if (is_array($message))
            $message = implode(" ", $message);

        if (!is_string($message))
            throw new \FatalErrorException(
                'Argument 2 (message) must be a string or an array.'
            );

        $this->response['message'] = $message;
    }

    public function backendRequest($service, $endpoint, $data=null,
        $method=null, $property=true
    ) {
        if (is_null($data)) {
            $data = $this->request->all();
        }

        if (is_null($method)) {
            $method = $this->request->method();
        }

        $backendResponse = RRHelper::sendRequest(
            $method,
            $service,
            '/'.$endpoint,
            ['query' => $data]
        );

        if ($backendResponse['code'] != 200) {
            $data = $backendResponse['json'];
            $data['service'] = $service;
            $data = json_encode($data, JSON_UNESCAPED_SLASHES);

            if ($backendResponse['json']['exception'] == 'UnauthorizedHttpException') {
                eval(
                    'throw new '.$backendResponse['json']['exception'].
                    '(null, "{$data}");'
                );
            } else {
                eval(
                    'throw new '.$backendResponse['json']['exception'].
                    '(\'$data\');'
                );
            }
        }

        /*
            If true, store the response in our $this->response property to be
            accessible by other traits and the host controller:
        */
        if (!$property) {
            return $backendResponse;
        }

        $this->response = $backendResponse;
    }

    public function backendResponse()
    {
        return RRHelper::prettyJson(
            $this->response['code'],
            $this->response['json']
        );
    }
}
