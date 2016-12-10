<?php

namespace Revolve\Microservice\Http\Middleware;

use Cache;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Revolve\Microservice\Backend\BackendRequest;

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
            $tokenData = $tokenFromCache['tokenObject'];
        } else {
            //
            // Otherwise, let's make a hard request to api-service-users
            $rq = new BackendRequest('users');
            $rq->dontThrowErrors();

            // $auth = ['Authorization' => request()->header('Authorization')];
            // Authorization header is automatically passed through.

            $rq->post('oauth/verify', []);

            if ($rq->code() != 200) {
                throw new AuthenticationException('Invalid access token.');
            }

            $tokenData = $rq->getResponse()['token'];
        }

        app()->singleton('OAuthAccessToken', function ($app) use ($tokenData) {
            return $tokenData;
        });

        return $next($request);
    }
}
