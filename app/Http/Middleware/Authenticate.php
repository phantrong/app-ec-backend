<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (!$request->expectsJson()) {
            return route('login');
        }
    }

    protected function unauthenticated($request, $guards)
    {
        throw new HttpResponseException(response()->json([
            'success'   => false,
            "messages" => 'Unauthorized bearer token',
            "errorCode" => config('errorCodes.common.token_invalid')
        ], JsonResponse::HTTP_UNAUTHORIZED));
    }
}
