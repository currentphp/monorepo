<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Laravel\Fortify\Http\Controllers\NewPasswordController;

class ResetPasswordController extends NewPasswordController
{
    public function store(Request $request): Responsable
    {
        // Reject malformed tokens before the broker passes them to the hasher.
        $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'password_confirmation' => ['required', 'string'],
        ]);

        return parent::store($request);
    }
}
