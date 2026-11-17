<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Demo tasks from database/fixtures/tasks.json; each belongs to a demo project.
 *
 * @property int $id
 * @property int $project_id
 * @property string $title
 */
#[Fillable(['id', 'project_id', 'title'])]
final class TaskExample extends Model
{
    /** @return list<self> */
    public static function examples(): array
    {
        return array_map(fn (array $record): self => new self($record), Fixtures::load('tasks'));
    }

    /** @return list<self> */
    public static function forProject(ProjectExample $project): array
    {
        return array_values(array_filter(self::examples(), fn (self $task): bool => $task->project_id === $project->id));
    }

    public static function findExample(ProjectExample $project, string $id): ?self
    {
        foreach (self::forProject($project) as $task) {
            if ((string) $task->id === $id) {
                return $task;
            }
        }

        return null;
    }

    public function project(): ProjectExample
    {
        return ProjectExample::findExample((string) $this->project_id)
            ?? throw new \LogicException("Task {$this->id} references unknown project {$this->project_id}.");
    }
}
