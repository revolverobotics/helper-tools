<?php

namespace App\Submodules\ToolsLaravelMicroservice\App\Classes;

use App\Submodules\ToolsLaravelMicroservice\App\Classes\BackendRequest;

use Cache;

/**
 * Takes a user or client token as input and retrieves the associated
 * user_id or client_id.
 *
 * For backend services to check with api-service-users the access/scope
 * of a user/client token.
 */
class BackendAuthorizer
{
    /**
     * Connection to the api-service-users microservice
     *
     * @var BackendRequest
     */
    protected $connection;

    protected $grant;

    public function __construct()
    {
        $this->connection = new BackendRequest('users');
    }

    public function lookup(string $OAuthToken)
    {
        $grant = Cache::tags(['grants'])->get($OAuthToken);

        if (is_null($grant)) {
            $response = $this->connection->post(
                'oauth',
                ['access_token' => $OAuthToken]
            );

            if ($response->code() != 200) {
                throw new \BackendException(
                    $response->code(),
                    $response->content()
                );
            }

            $grant = $response->content();
        }

        $this->grant = $grant;

        return $grant;
    }

    public function getGrant()
    {
        if (is_null($this->grant)) {
            return "Error.. You must run the lookup() method first.";
        }

        return json_decode($this->grant, true);
    }
}
