<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Docs\DocsCatalog;
use App\Docs\MarkdownRenderer;
use App\Docs\Sequence;
use Current\Facades\Current;
use Illuminate\Contracts\View\View;

class GuideController
{
    /**
     * Shows the guide for the current page: Current maps the route to its page,
     * and the page ID is the guide's ID.
     */
    public function __invoke(DocsCatalog $docs, MarkdownRenderer $markdown): View
    {
        $guide = $docs->get((string) Current::pageId());

        return view('guide', [
            'guide' => $guide,
            'rendered' => $markdown->render($docs->body($guide)),
            'sequence' => Sequence::around(Current::menu('docs'), $guide->id === 'docs'),
        ]);
    }
}
