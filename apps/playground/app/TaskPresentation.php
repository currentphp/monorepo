<?php

declare(strict_types=1);

namespace App;

use Current\Actions\ActionItem;
use Current\Actions\Actions;
use Current\Subjects\SubjectItem;
use Current\Subjects\Subjects;

/** Common task content for the three rendering transports. */
final class TaskPresentation
{
    /** @return array{rows: list<array{subject: SubjectItem, actions: list<ActionItem>}>, form: array{url: string, method: string, value: string}|null} */
    public static function data(string $pageId, ProjectExample $project, ?TaskExample $task, Rendering $rendering): array
    {
        $form = match ($pageId) {
            'tasks.create' => ['url' => $rendering->url(route('tasks.store', $project)), 'method' => 'post', 'value' => ''],
            'tasks.edit' => ['url' => $rendering->url(route('tasks.update', [$project, $task])), 'method' => 'put', 'value' => $task->title ?? ''],
            default => null,
        };

        return [
            'form' => $form,
            'rows' => $form !== null ? [] : array_map(fn (TaskExample $item): array => [
                'subject' => app(Subjects::class)->of($item),
                'actions' => app(Actions::class)->for($item),
            ], TaskExample::forProject($project)),
        ];
    }
}
