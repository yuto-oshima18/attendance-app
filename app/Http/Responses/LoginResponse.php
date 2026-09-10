<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->is('admin/login')) {
            return redirect()->route('admin.attendance.index');
        }

        return redirect()->route('attendance.create');
    }
}
