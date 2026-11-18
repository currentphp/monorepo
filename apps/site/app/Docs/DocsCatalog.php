<?php

declare(strict_types=1);

namespace App\Docs;

use League\CommonMark\Extension\FrontMatter\Data\SymfonyYamlFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser;
use LogicException;
use Symfony\Component\Finder\Finder;

/**
 * Reads content/docs into guides, so routes and Current pages can be
 * registered from the files on every boot, including cached-route boots.
 *
 * Paths map to IDs: docs/index.md is "docs", docs/subjects.md is
 * "docs.subjects", and docs/getting-started/menus.md is
 * "docs.getting-started.menus". Each folder needs an index.md.
 */
final class DocsCatalog
{
    /** @var array<string, Guide>|null */
    private ?array $guides = null;

    public function __construct(private readonly string $root) {}

    /**
     * @return array<string, Guide> Guides keyed by ID, parents before children, siblings in order.
     */
    public function all(): array
    {
        return $this->guides ??= $this->load();
    }

    public function find(string $id): ?Guide
    {
        return $this->all()[$id] ?? null;
    }

    public function get(string $id): Guide
    {
        return $this->find($id) ?? throw new LogicException("Unknown guide [{$id}].");
    }

    /**
     * @return list<Guide>
     */
    public function children(string $id): array
    {
        return array_values(array_filter(
            $this->all(),
            fn (Guide $guide): bool => $guide->parentId === $id,
        ));
    }

    /**
     * @return list<Guide>
     */
    public function routable(): array
    {
        return array_values(array_filter($this->all(), fn (Guide $guide): bool => $guide->routable));
    }

    /**
     * The Markdown body without its front matter.
     */
    public function body(Guide $guide): string
    {
        return $this->parse($guide->file)[1];
    }

    /**
     * @return array<string, Guide>
     */
    private function load(): array
    {
        if (! is_file($this->root.'/index.md')) {
            throw new LogicException("The docs root [{$this->root}] has no index.md.");
        }

        $guides = [];

        $files = Finder::create()->files()->in($this->root)->name('*.md')->sortByName();

        foreach ($files as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $guide = $this->guide($relative, $file->getPathname());
            $guides[$guide->id] = $guide;
        }

        foreach ($guides as $guide) {
            if ($guide->parentId !== null && ! isset($guides[$guide->parentId])) {
                throw new LogicException("Guide [{$guide->id}] is in a folder without an index.md.");
            }
        }

        return $this->ordered($guides, null);
    }

    private function guide(string $relative, string $file): Guide
    {
        [$meta, $body] = $this->parse($file);

        $segments = explode('/', substr($relative, 0, -3));

        if (end($segments) === 'index') {
            array_pop($segments);
        }

        $id = implode('.', ['docs', ...$segments]);
        $parentId = $segments === [] ? null : implode('.', ['docs', ...array_slice($segments, 0, -1)]);

        $title = $this->string($meta, 'title', $relative, required: true);
        $order = $meta['order'] ?? null;

        if (! is_int($order)) {
            throw new LogicException("Guide [{$relative}] needs an integer order.");
        }

        return new Guide(
            id: $id,
            parentId: $parentId,
            path: implode('/', ['docs', ...$segments]),
            file: $file,
            title: $title,
            label: $this->string($meta, 'label', $relative) ?? $title,
            description: $this->string($meta, 'description', $relative, required: true),
            order: $order,
            banner: $this->string($meta, 'banner', $relative),
            routable: trim($body) !== '',
        );
    }

    /**
     * @param  array<string, Guide>  $guides
     * @return array<string, Guide>
     */
    private function ordered(array $guides, ?string $parentId): array
    {
        $siblings = array_filter($guides, fn (Guide $guide): bool => $guide->parentId === $parentId);
        uasort($siblings, fn (Guide $a, Guide $b): int => [$a->order, $a->id] <=> [$b->order, $b->id]);

        $ordered = [];

        foreach ($siblings as $id => $guide) {
            $ordered[$id] = $guide;
            $ordered += $this->ordered($guides, $id);
        }

        return $ordered;
    }

    /**
     * @return array{0: array<mixed>, 1: string}
     */
    private function parse(string $file): array
    {
        $contents = file_get_contents($file);

        if ($contents === false) {
            throw new LogicException("Guide [{$file}] cannot be read.");
        }

        $parsed = (new FrontMatterParser(new SymfonyYamlFrontMatterParser))->parse($contents);
        $meta = $parsed->getFrontMatter();

        return [is_array($meta) ? $meta : [], $parsed->getContent()];
    }

    /**
     * @param  array<mixed>  $meta
     * @return ($required is true ? string : string|null)
     */
    private function string(array $meta, string $key, string $file, bool $required = false): ?string
    {
        $value = $meta[$key] ?? null;

        if ($value === null && ! $required) {
            return null;
        }

        if (! is_string($value) || trim($value) === '') {
            throw new LogicException("Guide [{$file}] needs a non-empty {$key}.");
        }

        return $value;
    }
}
