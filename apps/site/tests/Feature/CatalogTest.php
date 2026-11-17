<?php

declare(strict_types=1);

use App\Docs\DocsCatalog;

function catalogFrom(array $files): DocsCatalog
{
    $root = sys_get_temp_dir().'/site-catalog-'.bin2hex(random_bytes(4));

    foreach ($files as $path => $contents) {
        @mkdir(dirname($root.'/'.$path), recursive: true);
        file_put_contents($root.'/'.$path, $contents);
    }

    return new DocsCatalog($root);
}

function guideFile(string $title, int $order, string $body = 'Body.'): string
{
    return "---\ntitle: {$title}\ndescription: About {$title}.\norder: {$order}\n---\n\n{$body}\n";
}

it('maps paths to IDs, parents, and order', function (): void {
    $catalog = catalogFrom([
        'index.md' => guideFile('Intro', 0),
        'b.md' => guideFile('B', 2),
        'a/index.md' => guideFile('A', 1, ''),
        'a/two.md' => guideFile('Two', 2),
        'a/one.md' => guideFile('One', 1),
    ]);

    expect(array_keys($catalog->all()))->toBe(['docs', 'docs.a', 'docs.a.one', 'docs.a.two', 'docs.b'])
        ->and($catalog->get('docs.a.one')->parentId)->toBe('docs.a')
        ->and($catalog->get('docs.a.one')->path)->toBe('docs/a/one')
        ->and($catalog->get('docs.a')->routable)->toBeFalse()
        ->and($catalog->get('docs')->parentId)->toBeNull();
});

it('rejects a guide without a title', function (): void {
    catalogFrom([
        'index.md' => guideFile('Intro', 0),
        'bad.md' => "---\ndescription: x\norder: 1\n---\n\nBody\n",
    ])->all();
})->throws(LogicException::class, 'needs a non-empty title');

it('rejects a guide without an integer order', function (): void {
    catalogFrom([
        'index.md' => guideFile('Intro', 0),
        'bad.md' => "---\ntitle: Bad\ndescription: x\norder: first\n---\n\nBody\n",
    ])->all();
})->throws(LogicException::class, 'needs an integer order');

it('rejects a folder without an index', function (): void {
    catalogFrom([
        'index.md' => guideFile('Intro', 0),
        'a/one.md' => guideFile('One', 1),
    ])->all();
})->throws(LogicException::class, 'without an index.md');
