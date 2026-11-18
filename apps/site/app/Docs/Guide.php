<?php

declare(strict_types=1);

namespace App\Docs;

/**
 * One Markdown file under content/docs, described by its front matter.
 *
 * A guide becomes a Current page with the same ID. A section index without a
 * body is a route-less page: it groups guides in the sidebar and breadcrumbs.
 */
final readonly class Guide
{
    public function __construct(
        public string $id,
        public ?string $parentId,
        public string $path,
        public string $file,
        public string $title,
        public string $label,
        public string $description,
        public int $order,
        public ?string $banner,
        public bool $routable,
    ) {}

    public function routeName(): string
    {
        return $this->id;
    }
}
