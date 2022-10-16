<?php

namespace App\Http\Middleware;

use App\Enums\EnumStaff;
use App\Enums\EnumStore;
use Closure;
use Illuminate\Http\Request;

class IsOwnerShop
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
                && $staff->tokenCan('auth:staff')
                && $staff->status == EnumStaff::STATUS_ACCESS
                && $staff->store->status == EnumStore::STATUS_CONFIRMED
                && $staff->is_owner == EnumStaff::IS_OWNER) {
            return $next($request);
        }
        return response()->json([
            'success' => false,
            'message' => "Permission denied"
        ]);
    }
}
