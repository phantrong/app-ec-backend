<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LanguageExportMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        //Check header request and set language default
        $lang =  $request->header('Language') ?? 'jp';

        // set language
        app()->setLocale($lang);

        //Continue request
        return $next($request);
    }
}
