<?php

namespace Revolve\Microservice\Backend;

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
     * Authenticates a token and returns a list of associated scopes
     *
     * @param  string   $token
     *
     * @return boolean  Returns true if token is valid
     */
    public function validateToken($token)
    {
        $tokenFromCache = Cache::tags(['tokens'])->get($token);

        if (!is_null($tokenFromCache) &&
            $token == $tokenFromCache['access_token'] &&
            $tokenFromCache['expires_at'] > time()
        ) {
            return [
                'valid'  => true,
                'scopes' => $tokenFromCache['scopes']
            ];
        }

        // TODO:
        // If we want to implement a check to api-service-users, we should
        // add an option to bypass the cache and send a hard request to
        // the backend (in case a user doesn't trust the cache). That
        // route should be severely rate-limited, e.g., 1-2 requests per day.
        // A token should last 15 days so that is more than acceptable.

        return false;
    }
}

