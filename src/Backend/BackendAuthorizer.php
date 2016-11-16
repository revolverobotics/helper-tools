<?php

namespace Revolve\Microservice\Backend;

use Cache;
use Illuminate\Auth\AuthenticationException;

/**
 * Class for interacting with our backend OAuth 2 server.
 *
 * @package tools-laravel-microservice
 * @author  Timothy Huang
 */
class BackendAuthorizer
{
    /**
     * Validates a token via Cache and returns a set of scopes if true
     *
     * @param  string   $token
     * @return boolean  Returns true if token is valid
     */
    public function validateTokenFromCache($token)
    {
        $tokenFromCache = Cache::tags(['tokens'])->get($token);

        if (config('app.debug')) {
            print_r($tokenFromCache);
        }

        $exists = !is_null($tokenFromCache);
        $isEqual = $token == $tokenFromCache['access_token'];
        $isRevoked = $tokenFromCache['tokenObject']['revoked'] == 1;
        $isExpired = strtotime($tokenFromCache['tokenObject']['expires_at']) < time();

        if ($exists && $isEqual && !$isExpired && !$isRevoked) {
            return ['scopes' => implode(' ', $tokenFromCache['tokenObject']['scopes'])];
        }

        // TODO:
        // If we want to implement a check to api-service-users, we should
        // add an option to bypass the cache and send a hard request to
        // the backend (in case a user doesn't trust the cache). That
        // route should be severely rate-limited, e.g., 1-2 requests per day.
        // A token should last 15 days so that is more than acceptable.

        throw new AuthenticationException(
            'The token is invalid or cannot be found in Cache.'
        );
    }
}

