<?php

declare(strict_types=1);

namespace App\Docs;

use Current\Navigation\MenuItem;

/**
 * Previous and next links, read from the resolved docs menu in reading order.
 *
 * The site's own stand-in for a core reader (board card WK-037).
 */
final readonly class Sequence
{
    public function __construct(
        public ?MenuItem $previous,
        public ?MenuItem $next,
    ) {}

    /**
     * @param  list<MenuItem>  $menu
     * @param  bool  $beforeMenu  The current page precedes the menu, as the docs index does.
     */
    public static function around(array $menu, bool $beforeMenu = false): self
    {
        $links = array_values(array_filter(self::flatten($menu), fn (MenuItem $item): bool => $item->url !== null));

        if ($beforeMenu) {
            return new self(null, $links[0] ?? null);
        }

        foreach ($links as $index => $item) {
            if ($item->current) {
                return new self($links[$index - 1] ?? null, $links[$index + 1] ?? null);
            }
        }

        return new self(null, null);
    }

    /**
     * @param  list<MenuItem>  $items
     * @return list<MenuItem>
     */
    private static function flatten(array $items): array
    {
        $flat = [];

        foreach ($items as $item) {
            $flat[] = $item;
            array_push($flat, ...self::flatten($item->children));
        }

        return $flat;
    }
}
