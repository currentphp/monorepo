<?php

declare(strict_types=1);

namespace Prototype;

use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Synthetic identity for this isolated proof, not an authentication implementation. */
final class Identity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = new GenericUser(['id' => 1, 'name' => 'Synthetic maintainer']);
        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
