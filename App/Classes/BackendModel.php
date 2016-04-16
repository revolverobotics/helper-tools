<?php

namespace App\Submodules\ToolsLaravelMicroservice\App\Classes;

use ArrayAccess;
use BackendRequest;

/*
    Provides a base interface for retrieving and updating models from
    our backend microservices
*/

abstract class BackendModel implements ArrayAccess
{
    /**
     * Connection to the service being queried
     *
     * @var BackendRequest
     */
    protected $connection;

    /**
     * Service from which we want to retrieve the model
     *
     * @var string
     */
    protected $service;

    /**
     * Name of the model being queried, e.g., 'device', or 'location'
     *
     * @var string
     */
    protected $modelName;

    /**
     * Dataset of the retrieved model
     *
     * @var string
     */
    protected $modelData;

    /**
     * Instantiate the model with a connection to the backend
     *
     * @var string
     */
    public function __construct(
        string $modelName,
        string $service,
        BackendRequest $connection
    ) {
        $this->modelName = $modelName;

        $this->connection = $connection($service);
    }

    public function get($data)
    {
        $response = $this->connection->get($this->modelName, $data);

        if ($response->code() == 200) {
            // set $this->modelData with the response
        }
    }

    public function post($data)
    {
        $response = $this->connection->post($this->modelName, $data);

        if ($response->code() == 200) {
            // update $this->modelData with the response
        }
    }

    public function put($data)
    {
        $this->connection->put($this->modelName, $data);

        if ($response->code() == 200) {
            // update $this->modelData with the response
        }
    }

    public function patch($data)
    {
        $this->connection->patch($this->modelName, $data);

        if ($response->code() == 200) {
            // update $this->modelData with the response
        }
    }

    public function delete($data)
    {
        $this->connection->delete($this->modelName, $data);

        if ($response->code() == 200) {
            // $this->modelData = null
        }
    }
}
