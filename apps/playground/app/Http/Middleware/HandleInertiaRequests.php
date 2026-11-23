<?php

namespace App\Http\Middleware;

use App\Rendering;
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
            'renderingChoices' => function () use ($request): array {
                $value = $request->query('view', 'inertia');
                $rendering = is_string($value) ? Rendering::tryFrom($value) : null;

                // Error responses share these props too; never reject the input again.
                return $rendering !== null && preg_match('~^(projects|onboarding)(/|$)~', $request->path()) === 1
                    ? $rendering->choices($request) : [];
            },
            // Resolved at render time, after route-model binding.
            'current' => CurrentProps::make()->menus('primary', 'footer')
                ->pagePresentation()->subjectPresentation()->actions(),
            'status' => fn (): mixed => $request->session()->get('status'),
        ];
    }
}
