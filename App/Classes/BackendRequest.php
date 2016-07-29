<?php

namespace App\Submodules\ToolsLaravelMicroservice\App\Classes;

use GuzzleHttp\Client;
use App\Submodules\ToolsLaravelMicroservice\App\Exceptions\BackendException;

class BackendRequest
{
    /**
     * Enumerates our available microservices to be queried
     *
     * @var array
     */
    protected $services = [
        'devices',
        'locations',
        'users',
        'auditing',
    ];

    /**
     * Client object that is responsible for
     * making the request
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * HTTP method to be used for the request
     *
     * @var string
     */
    protected $method;

    /**
     * Base URL at which the microservice can
     * be reached, e.g., 'devices.kubi-vpc.com'
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Payload including query data, headers, and more,
     * to be sent with the request
     *
     * @var array
     */
    protected $payload;

    /**
     * Request headers from our parent app
     *
     * @var array
     */
    protected $parentRequestHeaders;

    /**
     * Request headers for our backend request
     *
     * @var array
     */
    protected $requestHeaders;

    /**
     * Response from the backend microservice.  Will always be JSON.
     *
     * @var array
     */
    protected $response;

    /**
     * Status code of the response from the backend microservice.
     *
     * @var int
     */
    protected $code;

    /**
     * Domain at which our microservices live
     *
     * @var string
     */
    const MICROSERVICE_DOMAIN = 'kubi-vpc';

    /**
     * Instantiate the client
     *
     * @param string $service
     */
    public function __construct(string $service)
    {
        $this->client = new Client();

        $this->validateService($service);

        $this->setBaseUrl($service);
    }

    /**
     * Ensures that we're attempting to reach a valid microservice
     *
     * @param string $service
     * @return void
     *
     * @throws \Exception
     */
    protected function validateService($service)
    {
        if (!in_array($service, $this->services)) {
            throw new \Exception('Invalid service provided.');
        }
    }

    /**
     * Gets the correct url extension based on our app environment
     *
     * @return void
     */
    protected function detectEnvironment()
    {
        $env = app()->environment();

        if (!is_null(env('VPC_EXTENSION', null))) {
            return env('VPC_EXTENSION');
        }

        if ($env == 'production') {
            return 'com';
        }

        return 'stage';
    }

    /**
     * Set the baseUrl
     *
     * @param string $service
     * @return void
     */
    public function setBaseUrl(string $service)
    {
        $this->baseUrl = $service.'.'.
                         static::MICROSERVICE_DOMAIN.'.'.
                         $this->detectEnvironment();
    }

    /**
     * Make a GET request
     *
     * @param string $path
     * @param array $queryData
     * @return array
     */
    public function get(string $path, array $queryData)
    {
        $this->method = 'GET';

        return $this->send($path, $queryData);
    }

    /**
     * Make a POST request
     *
     * @param string $path
     * @param array $queryData
     * @return array
     */
    public function post(string $path, array $queryData)
    {
        $this->method = 'POST';

        return $this->send($path, $queryData);
    }

    /**
     * Make a PUT request
     *
     * @param string $path
     * @param array $queryData
     * @return array
     */
    public function put(string $path, array $queryData)
    {
        $this->method = 'PUT';

        return $this->send($path, $queryData);
    }

    /**
     * Make a PATCH request
     *
     * @param string $path
     * @param array $queryData
     * @return array
     */
    public function patch(string $path, array $queryData)
    {
        $this->method = 'PATCH';

        return $this->send($path, $queryData);
    }

    /**
     * Make a DELETE request
     *
     * @param string $path
     * @param array $queryData
     * @return array
     */
    public function delete(string $path, array $queryData)
    {
        $this->method = 'DELETE';

        return $this->send($path, $queryData);
    }

    /**
     * Make an ANY request
     *
     * @param string $path
     * @param array $queryData
     * @return array
     */
    public function any(string $path, array $queryData, string $method)
    {
        $this->method = $method;

        return $this->send($path, $queryData);
    }

    /**
     * Send the request
     *
     * @return array
     */
    protected function send(string $path, array $queryData)
    {
        $this->setRequestHeaders();

        $this->setPayloadData($queryData);

        $this->payload['http_errors'] = false;
        // don't fail on error (400, 500, etc.)

        $rawResponse = $this->client->request(
            $this->method,
            'http://'.$this->baseUrl.'/'.$path,
            $this->payload
        );

        $this->response = json_decode($rawResponse->getBody(), true);
        $this->code = $rawResponse->getStatusCode();

        if ($this->code != 200) {
            throw new BackendException(
                $this->code,
                json_encode($this->response)
            );
        }

        return $this->response;
    }

    /**
     * Set the headers for our backend request
     *
     * @return void
     */
    protected function setRequestHeaders()
    {
        $this->requestHeaders = app()->request->header();

        $this->requestHeaders['host'][0] = $this->baseUrl;
        $this->requestHeaders['connection'][0] = 'close';
        unset($this->requestHeaders['content-type']);
        unset($this->requestHeaders['content-length']);

        $this->payload['headers'] = $this->requestHeaders;
    }

    /**
     * GET and non-GET (POST, PUT, PATCH, etc.) requests
     * require different keys for the $payload
     *
     * @param array $queryData
     * @return void
     */
    protected function setPayloadData(array $queryData)
    {
        if ($this->method == 'GET') {
            $dataWrapper = 'query';
        } else {
            $dataWrapper = 'form_params';
        }

        $this->payload[$dataWrapper] = $queryData;
    }

    /**
     * Return the response received from the backend microservice
     *
     * @return void
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Return the status code received from the backend microservice
     *
     * @return void
     */
    public function code()
    {
        return $this->getStatusCode();
    }

    /**
     * Return the status code received from the backend microservice
     *
     * @return void
     */
    public function getStatusCode()
    {
        return $this->code;
    }
}
