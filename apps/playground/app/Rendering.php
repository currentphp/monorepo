<?php

declare(strict_types=1);

namespace App;

use Illuminate\Http\Request;
use Illuminate\Support\Uri;

/** Rendering is a view choice; Current page identities and routes stay canonical. */
enum Rendering: string
{
    case Inertia = 'inertia';
    case Blade = 'blade';
    case Livewire = 'livewire';
    case Json = 'json';

    public static function forRequest(Request $request): self
    {
        $value = $request->query('view', 'inertia');
        abort_unless(is_string($value), 404);

        return self::tryFrom($value) ?? abort(404);
    }

    public function url(string $url): string
    {
        if ($this === self::Inertia || ! preg_match('~^/(projects|onboarding)(/|$)~', parse_url($url, PHP_URL_PATH) ?: '')) {
            return $url;
        }

        // Current's canonical links carry no rendering state.
        return (string) Uri::of($url)->withQuery(['view' => $this->value]);
    }

    /** @return list<array{label: string, url: string, current: bool}> */
    public function choices(Request $request): array
    {
        return array_map(fn (self $view): array => [
            'label' => $view === self::Json ? 'JSON API' : ucfirst($view->value),
            'url' => $request->fullUrlWithQuery(['view' => $view->value]),
            'current' => $view === $this,
        ], self::cases());
    }
}
