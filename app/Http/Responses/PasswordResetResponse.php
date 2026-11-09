<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Laravel\Fortify\Http\Responses\PasswordResetResponse as FortifyPasswordResetResponse;
use Symfony\Component\HttpFoundation\Response;

class PasswordResetResponse extends FortifyPasswordResetResponse
{
    public function toResponse($request): Response
    {
        $response = parent::toResponse($request);

        // Leave the token-bearing document and its no-referrer policy behind.
        return $response instanceof RedirectResponse
            ? Inertia::location($response)
            : $response;
    }
}
