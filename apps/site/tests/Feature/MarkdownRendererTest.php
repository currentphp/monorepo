<?php

declare(strict_types=1);

use App\Docs\MarkdownRenderer;

it('lists second- and third-level headings with their anchors', function (): void {
    $rendered = (new MarkdownRenderer)->render("# Title\n\n## First `step`\n\n### Detail\n\n#### Deep\n");

    expect($rendered->headings)->toBe([
        ['id' => 'first-step', 'text' => 'First step', 'level' => 2],
        ['id' => 'detail', 'text' => 'Detail', 'level' => 3],
    ])->and($rendered->html)->toContain('id="first-step"');
});

it('renders GitHub-style alerts as asides', function (): void {
    $html = (new MarkdownRenderer)->render("> [!WARNING]\n> Hiding is not *authorization*.\n\n> A plain quote.\n")->html;

    expect($html)
        ->toContain('<aside class="alert alert-warning"><p class="alert-title">Warning</p><p>Hiding is not <em>authorization</em>.</p>')
        ->toContain('<blockquote>');
});

it('highlights fenced code with light and dark themes', function (): void {
    $html = (new MarkdownRenderer)->render("```php\n\$pages->register();\n```\n")->html;

    expect($html)->toContain('class="phiki')->toContain('--phiki-dark');
});
