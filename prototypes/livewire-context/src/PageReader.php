<?php

declare(strict_types=1);

namespace Prototype;

use Current\Experience;
use Current\Facades\Current;

/** Fixture projection using only Current's supported public interface. */
final readonly class PageReader
{
    private Experience $experience;

    public function __construct(string $pageId, Project $project)
    {
        $this->experience = Current::forPage($pageId, ['project' => $project]);
    }

    /** @return array<string, mixed> */
    public function describe(): array
    {
        $reader = $this->experience;

        return [
            'pageId' => $reader->pageId(),
            'title' => $reader->title(),
            'description' => $reader->description(),
            'subject' => $reader->subject(),
            'subjectId' => $reader->subject()?->id,
            'subjectTitle' => $reader->subjectTitle(),
            'subjectDescription' => $reader->subjectDescription(),
            'breadcrumbs' => $reader->breadcrumbs(),
            'menu' => $reader->menu('primary'),
            'actions' => $reader->actions(),
            'userId' => $reader->user()?->getAuthIdentifier(),
        ];
    }
}
