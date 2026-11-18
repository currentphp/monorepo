<?php

namespace App\Http\Middleware;

use App\Prototype\Actions\Actions;
use Current\Inertia\CurrentProps;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user()?->only(['id', 'name', 'email', 'email_verified_at']),
                'canRegister' => (bool) config('fortify.registration_enabled'),
            ],
            // Resolved at render time, after route-model binding.
            'current' => CurrentProps::make()->menus('primary', 'footer'),
            // Actions prototype (WK-008): the current subject's and page's actions, shared by the app itself.
            'actions' => fn (): array => app(Actions::class)->current(),
            'status' => fn (): mixed => $request->session()->get('status'),
        ];
    }
}
