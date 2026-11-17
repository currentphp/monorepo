<?php

declare(strict_types=1);

namespace Current\Tests\Fixtures\CacheApp;

use Current\Navigation\Navigation;
use Current\Pages\Pages;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;

final class FixtureController
{
    public function index(): string
    {
        return 'Projects';
    }

    /** @return array<string, mixed> */
    public function show(Request $request, Pages $pages, Navigation $navigation): array
    {
        $request->setUserResolver(fn () => $request->header('X-Test-User') === 'alice' ? new GenericUser(['id' => 'alice']) : null);

        return [
            'pageId' => $pages->current()?->id,
            'user' => $pages->context()->user?->getAuthIdentifier(),
            'breadcrumbs' => $navigation->breadcrumbs(),
            'primary' => $navigation->menu('primary'),
            'footer' => $navigation->menu('footer'),
        ];
    }
}
