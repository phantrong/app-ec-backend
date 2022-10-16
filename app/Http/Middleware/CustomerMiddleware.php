<?php

namespace App\Http\Middleware;

use App\Enums\EnumCustomer;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()->tokenCan('auth:customer') &&
            $request->user()->status == EnumCustomer::STATUS_ACTIVE) {
            return $next($request);
        }
        return response()->json([
            'success' => false,
            'message' => "Permission denied"
        ], JsonResponse::HTTP_UNAUTHORIZED);
    }
}
