<?php

declare(strict_types=1);

namespace App\WavyKit;

use WavyKit\Facades\Current;
use WavyKit\Navigation\Breadcrumb;
use WavyKit\Navigation\MenuItem;

/** Resolves WavyKit component output for the current request, shared with every Inertia page. */
final readonly class SharedProps
{
    /** Menus every page receives; add a name after registering the menu. */
    public const array MENUS = ['primary', 'footer'];

    /**
     * @return array{
     *     pageId: ?string,
     *     breadcrumbs: list<Breadcrumb>,
     *     menus: array<string, list<MenuItem>>,
     * }
     */
    public function toArray(): array
    {
        $menus = [];
        foreach (self::MENUS as $name) {
            $menus[$name] = Current::menu($name);
        }

        return [
            'pageId' => Current::pageId(),
            'breadcrumbs' => Current::breadcrumbs(),
            'menus' => $menus,
        ];
    }
}
