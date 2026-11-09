<?php

declare(strict_types=1);

namespace App\WavyKit;

use WavyKit\Navigation\MenuItem;
use WavyKit\Navigation\Navigation;
use WavyKit\Pages\Pages;

/** Resolves WavyKit component output for the current request, shared with every Inertia page. */
final readonly class SharedProps
{
    /** Menus every page receives; add a name after registering the menu. */
    public const array MENUS = ['primary', 'footer'];

    public function __construct(
        private Pages $pages,
        private Navigation $navigation,
    ) {}

    /**
     * @return array{
     *     pageId: ?string,
     *     breadcrumbs: list<array{pageId: string, label: string, url: ?string, current: bool}>,
     *     menus: array<string, list<MenuItem>>,
     * }
     */
    public function toArray(): array
    {
        $menus = [];
        foreach (self::MENUS as $name) {
            $menus[$name] = $this->navigation->menu($name);
        }

        return [
            'pageId' => $this->pages->page()?->id,
            'breadcrumbs' => $this->pages->breadcrumbs(),
            'menus' => $menus,
        ];
    }
}
