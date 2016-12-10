<?php

namespace Revolve\Microservice\Http\Middleware;

use Cache;
use Closure;
use Illuminate\Auth\AuthenticationException;

/**
 * Middleware for checking the validity of an access token via cache,
 * and then making a hard request to the api-service-users server if the
 * token is invalid.
 *
 * @package tools-laravel-microservice
 * @author  Timothy Huang
 */
class CheckAccessToken
{
    /**
     * Validates a token via Cache and returns a set of scopes if true.
     * The intended use for this middleware is in backend api services
     * not including api-service-users.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Check for Authorization header
        if (!request()->hasHeader('Authorization')) {
            throw new AuthenticationException('No access token given.');
        }

        // Retrieve token from header
        $token = request()->header('Authorization');
        $token = str_replace("Bearer ", "", $token);

        // Check the cache for the token
        $tokenFromCache = Cache::tags(['tokens'])->get($token);

        $exists = !is_null($tokenFromCache);
        $isEqual = $token == $tokenFromCache['access_token'];
        $isRevoked = $tokenFromCache['tokenObject']['revoked'] == 1;
        $isExpired = strtotime($tokenFromCache['tokenObject']['expires_at']) < time();

        if ($exists && $isEqual && !$isExpired && !$isRevoked) {
            //
            // If token exists in cache and is valid, continue with the request

            print_r($tokenFromCache);
            \Log::debug($tokenFromCache);

            $tokenData = $tokenFromCache;
        } else {
            //
            // Otherwise, let's make a hard request to api-service-users
            $request  = new BackendRequest('users');

            $request->post(
                'oauth/verify',
                [],
                ['Authorization' => 'Bearer '.$token]
            );

            $response = $request->getResponse();

            print_r($response);
            \Log::debug($response);

            if ($request->code() != 200) {
                throw new AuthenticationException('Invalid access token.');
            }

            $tokenData = $response['token'];
        }

        app()->singleton('OAuthAccessToken', function ($app) use ($tokenData) {
            return $tokenData;
        });

        return $next($request);
    }
}
