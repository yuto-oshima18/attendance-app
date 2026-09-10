<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     * auth Middlewareで未ログインユーザーを検出したときのリダイレクト先を決める
     * 
     * @param Request $request
     * 
     * @return ?string
     */
    protected function redirectTo(Request $request): ?string
    {
        //return $request->expectsJson() ? null : null;

        if ($request->is('admin/*')) {
            return route('admin.login');
        }

        return route('login');
    }
}
