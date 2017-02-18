<?php

namespace Revolve\Microservice\Backend;

use GuzzleHttp\Client;
use Revolve\Microservice\Exceptions\BackendException;

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
     * Whether or not to throw an exception if an error code was returned
     *
     * @var boolean
     */
    protected $httpErrors = false;

    /**
     * Domain at which our microservices live
     *
     * @var string
     */
    const MICROSERVICE_DOMAIN = 'kubi';

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
     * Tell this class not to throw an exception if an http error code was received
     *
     */
    public function dontThrowErrors()
    {
        $this->httpErrors = false;
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
     * Set the baseUrl
     *
     * @param string $service
     * @return void
     */
    public function setBaseUrl(string $service)
    {
        if (str_contains(gethostname(), '.local')) {
            $extension = 'dev';
        } elseif (gethostname() == 'ip-10-0-0-14') {
            $extension = 'stage';
        } else {
            $extension = 'vpc';
        }

        $this->baseUrl = "http://{$service}.kubi.{$extension}";
    }

    /**
     * Make a GET request
     *
     * @param string $path
     * @param array $queryData
     * @return array
     */
    public function get(string $path, array $queryData = [], $headers = null)
    {
        $this->method = 'GET';

        return $this->send($path, $queryData, $headers);
    }

    /**
     * Make a POST request
     *
     * @param string $path
     * @param array $queryData
     * @return array
     */
    public function post(string $path, array $queryData = [], $headers = null)
    {
        $this->method = 'POST';

        return $this->send($path, $queryData, $headers);
    }

    /**
     * Make a PUT request
     *
     * @param string $path
     * @param array $queryData
     * @return array
     */
    public function put(string $path, array $queryData = [], $headers = null)
    {
        $this->method = 'PUT';

        return $this->send($path, $queryData, $headers);
    }

    /**
     * Make a PATCH request
     *
     * @param string $path
     * @param array $queryData
     * @return array
     */
    public function patch(string $path, array $queryData = [], $headers = null)
    {
        $this->method = 'PATCH';

        return $this->send($path, $queryData, $headers);
    }

    /**
     * Make a DELETE request
     *
     * @param string $path
     * @param array $queryData
     * @return array
     */
    public function delete(string $path, array $queryData = [], $headers = null)
    {
        $this->method = 'DELETE';

        return $this->send($path, $queryData, $headers);
    }

    /**
     * Make an ANY request
     *
     * @param string $path
     * @param array $queryData
     * @return array
     */
    public function any(string $path, array $queryData, string $method, $headers = null)
    {
        $this->method = $method;

        return $this->send($path, $queryData, $headers);
    }

    /**
     * Send the request
     *
     * @return array
     */
    protected function send(string $path, array $input = [], $headers = null)
    {
        $payload = [];

        if ($this->method == 'POST') {
            $payload['multipart'] = [];

            foreach ($input as $key => $value) {
                $param = ['name' => $key, 'contents' => $value];
                array_push($payload['multipart'], $param);
            }
        } else {
            $dataWrapper = ($this->method == 'GET' ? 'query' : 'form_params');
            $payload[$dataWrapper] = $input;
        }

        $payload['headers'] = $this->setRequestHeaders($headers);
        $payload['http_errors'] = $this->httpErrors;

        $url      = $this->baseUrl.'/'.$path;
        $response = $this->client->request($this->method, $url, $payload);

        $this->code     = $response->getStatusCode();
        $this->response = json_decode($response->getBody()->getContents(), true);

        if ($this->code != 200) {
            throw new BackendException($this->code, json_encode($this->response));
        }

        return $this->response;
    }

    /**
     * Set the headers for our backend request
     *
     * @return void
     */
    protected function setRequestHeaders($headers)
    {
        $this->requestHeaders = app()->request->header();

        unset($this->requestHeaders['content-type']);
        unset($this->requestHeaders['content-length']);

        $this->requestHeaders['host'][0] = str_replace('http://', '', $this->baseUrl);
        // $this->requestHeaders['connection'][0] = 'close';

        if (is_null($headers)) {
            $headers = [];
        }

        return array_merge($this->requestHeaders, $headers);
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
