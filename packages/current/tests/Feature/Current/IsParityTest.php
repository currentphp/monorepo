<?php

declare(strict_types=1);

use Current\Facades\Current;
use Current\Pages\Page;
use Current\Pages\PageRegistry;
use Current\Tests\Inertia\Fixtures\ClientTypes;

// The client package's is() must decide exactly as Current::is(). This test
// records PHP's answers for a shared case table; the client's Node tests then
// compare is() against every case.
test('is() parity cases are written for the client package', function () {
    $pageIds = [
        'projects',
        'projects.show',
        'projects.tasks.show',
        'Dashboard',
        'a+b',
        'x(y)?',
        'a/b|c',
        'ünïcode.päge',
    ];
    app(PageRegistry::class)->register(...array_map(Page::make(...), $pageIds));

    $patternSets = [
        [],
        ['*'],
        ['projects'],
        ['projects.*'],
        ['*.show'],
        ['projects.*.show'],
        ['proj*cts'],
        ['*s'],
        ['Projects.*'],
        ['dashboard'],
        ['a+b'],
        ['a.b'],
        ['x(y)?'],
        ['x*'],
        ['a/b|c'],
        ['ünïcode.*'],
        ['nope', 'projects.show'],
        ['nope', 'other'],
    ];

    $cases = [];
    foreach ([null, ...$pageIds] as $pageId) {
        foreach ($patternSets as $patterns) {
            $cases[] = [
                'pageId' => $pageId,
                'patterns' => $patterns,
                'expected' => $pageId === null ? Current::is(...$patterns) : Current::forPage($pageId)->is(...$patterns),
            ];
        }
    }

    // The table must exercise both answers, and no page must never match.
    $matches = array_filter($cases, fn (array $case): bool => $case['expected']);
    expect($matches)->not->toBeEmpty()
        ->and(count($matches))->toBeLessThan(count($cases))
        ->and(array_filter($matches, fn (array $case): bool => $case['pageId'] === null))->toBeEmpty();

    $path = ClientTypes::parityCases();
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0o777, true);
    }
    file_put_contents($path, json_encode($cases, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");
});
