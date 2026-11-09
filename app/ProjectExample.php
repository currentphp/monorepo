<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * In-memory trial fixtures; no project table or persisted changes are used.
 *
 * @property int $id
 * @property string $name
 */
#[Fillable(['id', 'name'])]
final class ProjectExample extends Model
{
    /** @return list<self> */
    public static function examples(): array
    {
        return [
            new self(['id' => 42, 'name' => 'Apollo']),
            new self(['id' => 77, 'name' => 'Artemis']),
        ];
    }

    public static function findExample(string $id): ?self
    {
        foreach (self::examples() as $project) {
            if ((string) $project->id === $id) {
                return $project;
            }
        }

        return null;
    }
}
