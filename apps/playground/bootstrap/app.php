<?php

use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(AssignRequestId::class);
        $middleware->web(append: [
            AuthenticateSession::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['token']);
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            $status = $response->getStatusCode();
            $requestId = $request->attributes->get('request_id');
            if (! config('app.debug') && in_array($status, [403, 404, 419, 429, 500, 503])) {
                $headers = array_intersect_key($response->headers->all(), array_flip([
                    'retry-after', 'x-ratelimit-limit', 'x-ratelimit-remaining',
                ]));
                if ($request->expectsJson()) {
                    return response()->json(['message' => Response::$statusTexts[$status] ?? 'Request failed', 'request_id' => $requestId], $status, $headers);
                }

                $safeResponse = Inertia::render('error', ['status' => $status, 'requestId' => $requestId])
                    ->toResponse($request)->setStatusCode($status);
                $safeResponse->headers->add($headers);

                return $safeResponse;
            }

            return $response;
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
