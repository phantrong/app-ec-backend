<?php

namespace App\Http\Middleware;

use App\Enums\EnumStaff;
use App\Enums\EnumStore;
use Closure;
use Illuminate\Http\Request;

class IsStaff
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $staff = $request->user();
        if ($staff
                && $staff->tokenCan(config('auth.token_staff'))
                && $staff->status == EnumStaff::STATUS_ACCESS
                && $staff->store->status == EnumStore::STATUS_CONFIRMED) {
            return $next($request);
        }
        return response()->json([
            'success' => false,
            'message' => "Permission denied"
        ]);
    }
}
