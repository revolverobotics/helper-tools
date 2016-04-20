<?php

namespace App\Submodules\ToolsLaravelMicroservice\App\Classes;

class BackendResponse
{
    /**
     * Response content returned from BackendRequest
     *
     * @var array
     */
    protected $content;

    /**
     * Status code of the response from the backend microservice.
     *
     * @var int
     */
    protected $code;

    /**
     * Instantiate the response object
     *
     * @param string $service
     */
    public function __construct($code, $content)
    {
        $this->code = $code;
        $this->content = $content;
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
