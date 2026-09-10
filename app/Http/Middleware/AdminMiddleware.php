<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     * admin_statusがtrueであるかを確認する。
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        if (! auth()->user()->admin_status) {
            abort(403);
        }

        return $next($request);
    }
}
