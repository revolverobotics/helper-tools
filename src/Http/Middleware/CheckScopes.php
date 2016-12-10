<?php

namespace Revolve\Microservice\Http\Middleware;

use Cache;
use Closure;
use Illuminate\Auth\AuthenticationException;

/**
 * Middleware for checking that all the given scopes are assigned to the
 * authorized access token.
 *
 * @package tools-laravel-microservice
 * @author  Timothy Huang
 */
class CheckScopes
{
    /**
     * Validates a token via Cache and returns a set of scopes if true.
     * The intended use for this middleware is in backend api services
     * not including api-service-users.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  array  $scopes
     * @return mixed
     */
    public function handle($request, Closure $next, ...$scopes)
    {
        // Check for a token authorized by CheckAccessToken
        try {
            $token = app()->make('OAuthAccessToken');
        } catch (\Exception $e) {
            throw new AuthenticationException('No token for current session.');
        }

        if (is_null($token['scopes'])) {
            throw new AuthenticationException('No scopes for given token.');
        }

        foreach ($scopes as $scope) {
            if (!in_array($scope, $token['scopes'])) {
                throw new AuthenticationException;
            }
        }

        return $next($request);
    }
}
