<?php

declare(strict_types=1);

namespace App;

use Current\Experience;

/** Shared projection using only Current's supported public interface. */
final class ExperienceData
{
    /** @return array<string, mixed> */
    public static function from(Experience $reader): array
    {
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
