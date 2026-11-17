<?php

declare(strict_types=1);

namespace App\WavyKit\Subjects;

use WavyKit\Facades\Current;

/**
 * Prototype (WK-019): the subject the active page presents.
 *
 * Stands in for a future `Current::subject()` in core. On a task page, where
 * both a project and a task are bound, this is the task.
 */
final readonly class CurrentSubject
{
    public function __construct(private SubjectRegistry $subjects) {}

    public function get(): ?Subject
    {
        $pageId = Current::pageId();
        $presented = $pageId === null ? null : $this->subjects->presentedBy($pageId);
        if ($presented === null) {
            return null;
        }
        $object = Current::context()->parameters()[$presented['parameter']] ?? null;

        return is_object($object) ? $this->subjects->of($object) : null;
    }
}
