<?php

namespace Revolve\Microservice\Exceptions;

use Symfony\Component\HttpKernel\Exception\RuntimeException;

class BackendException extends RuntimeException
{
    private $statusCode;

    public function __construct(
        $statusCode,
        $message = null,
        \Exception $previous = null
    ) {
        $this->statusCode = $statusCode;

        parent::__construct($message, $this->statusCode, $previous);
    }
}
