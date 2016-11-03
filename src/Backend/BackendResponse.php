<?php

namespace Revolve\Microservice\Backend;

class BackendResponse
{
    /**
     * Status code of the response from the backend microservice.
     *
     * @var int
     */
    protected $code;

    /**
     * Response content returned from BackendRequest
     *
     * @var array
     */
    protected $content;

    /**
     * Response headers returned from BackendRequest
     *
     * @var array
     */
    protected $headers;

    /**
     * Instantiate the response object
     *
     * @param string $service
     */
    public function __construct($code, $content, $headers = null)
    {
        $this->code = $code;
        $this->content = $content;
        $this->headers = $headers;
    }

    /**
     * Return the response content
     *
     * @return void
     */
    public function content()
    {
        return $this->content;
    }

    public function headers()
    {
        return $this->headers;
    }

    /**
     * Return the response status code
     *
     * @return void
     */
    public function code()
    {
        return $this->getStatusCode();
    }

    /**
     * Return the response status code
     *
     * @return void
     */
    public function getStatusCode()
    {
        return $this->code;
    }
}
