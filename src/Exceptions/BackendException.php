<?php

namespace Revolve\Microservice\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class BackendException extends HttpException
{
    private $statusCode;

    public function __construct(
        $statusCode,
        $message = null,
        \Exception $previous = null
    ) {
        $this->statusCode = $statusCode;

        parent::__construct($this->statusCode, $message, $previous);
    }
}
