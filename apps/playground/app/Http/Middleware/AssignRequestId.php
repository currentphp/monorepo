<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignRequestId
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        Context::flush();
        $requestId = (string) Str::uuid();
        $request->attributes->set('request_id', $requestId);
        Context::add('request_id', $requestId);

        try {
            $response = $next($request);
            $response->headers->set('X-Request-ID', $requestId);
            if ($request->is('reset-password*') || $request->is('email/verify*')) {
                $response->headers->set('Cache-Control', 'no-store');
                $response->headers->set('Referrer-Policy', 'no-referrer');
            }
            Log::info('Request completed', [
                'route' => $request->route()?->getName() ?? 'unmatched',
                'method' => $request->method(),
                'status' => $response->getStatusCode(),
            ]);

            return $response;
        } finally {
            Context::flush();
        }
    }
}
